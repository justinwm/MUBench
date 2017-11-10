<?php
namespace MuBench\ReviewSite\Controller;


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MuBench\ReviewSite\Models\Detector;
use MuBench\ReviewSite\Models\Experiment;
use MuBench\ReviewSite\Models\Finding;
use MuBench\ReviewSite\Models\Metadata;
use MuBench\ReviewSite\Models\Misuse;
use MuBench\ReviewSite\Models\Run;
use MuBench\ReviewSite\Models\Snippet;

class FindingsController extends Controller
{

    public function processData($experimentId, $run)
    {
        $projectId = $run->{'project'};
        $versionId = $run->{'version'};
        $misuseId = $run->{'misuse'};
        $detectorName = $run->{'detector'};

        $detector = Detector::firstOrCreate(['name' => $detectorName]);
        $experiment = Experiment::find($experimentId);

        $potential_hits = $run->{'potential_hits'};

        $this->createOrUpdateRunsTable($detector, $run);
        $this->updateRun($detector, $experiment, $projectId, $versionId, $run);
        if ($potential_hits) {
            $run = Run::of($detector)->in($experiment)->where(['project_muid' => $projectId, 'version_muid' => $versionId])->first();
            $this->createOrUpdateFindingsTable($detector, $potential_hits);
            $this->storeFindings($detector, $experiment, $projectId, $versionId, $misuseId, $run->id, $potential_hits);
        }
    }

    private function createOrUpdateRunsTable(Detector $detector, $run)
    {
        $propertyToColumnNameMapping = $this->getColumnNamesFromProperties($run);
        $propertyToColumnNameMapping = $this->removeDisruptiveStatsColumns($propertyToColumnNameMapping);
        $run = new \MuBench\ReviewSite\Models\Run;
        $run->setDetector($detector);
        $this->createOrUpdateTable($run->getTable(), $propertyToColumnNameMapping, array($this, 'createRunsTable'));
    }

    private function createOrUpdateFindingsTable(Detector $detector, $findings)
    {
        $propertyToColumnNameMapping = $this->getPropertyToColumnNameMapping($findings);
        $propertyToColumnNameMapping = $this->removeDisruptiveFindingsColumns($propertyToColumnNameMapping);
        $finding = new \MuBench\ReviewSite\Models\Finding;
        $finding->setDetector($detector);
        $this->createOrUpdateTable($finding->getTable(), $propertyToColumnNameMapping, array($this, 'createFindingsTable'));
    }

    private function createOrUpdateTable($table_name, $propertyToColumnNameMapping, $createFunc)
    {
        if (!Schema::hasTable($table_name)) {
            $createFunc($table_name);
        }
        $existing_columns = $this->getPropertyColumnNames($table_name);
        foreach ($propertyToColumnNameMapping as $column) {
            if (!in_array($column, $existing_columns)) {
                $this->addColumnToTable($table_name, $column);
            }
        }
    }

    private function getPropertyColumnNames($table_name)
    {
        return Schema::getColumnListing($table_name);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection used in createOrUpdateFindingsTable */
    private function createFindingsTable($table_name)
    {
        Schema::create($table_name, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('experiment_id');
            $table->integer('misuse_id');
            $table->string('project_muid', 30);
            $table->string('version_muid', 30);
            $table->string('misuse_muid', 30);
            $table->integer('startline')->nullable();
            $table->integer('rank');
            $table->text('file')->nullable();
            $table->text('method')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    /** @noinspection PhpUnusedPrivateMethodInspection used in createOrUpdateRunsTable */
    private function createRunsTable($table_name)
    {
        Schema::create($table_name, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('experiment_id');
            $table->string('project_muid', 30);
            $table->string('version_muid', 30);
            $table->float('runtime');
            $table->integer('number_of_findings');
            $table->string('result', 30);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    private function getPropertyToColumnNameMapping($entries)
    {
        $propertyToColumnNameMapping = [];
        foreach ($entries as $entry) {
            $propertyToColumnName = $this->getColumnNamesFromProperties($entry);
            foreach ($propertyToColumnName as $property => $column) {
                $propertyToColumnNameMapping[$property] = $column;
            }
        }
        return $propertyToColumnNameMapping;
    }

    private function getColumnNamesFromProperties($entry)
    {
        $propertyToColumnNameMapping = [];
        $properties = array_keys(get_object_vars($entry));
        foreach ($properties as $property) {
            // MySQL does not permit column names with more than 64 characters:
            // https://dev.mysql.com/doc/refman/5.7/en/identifiers.html
            $column_name = strlen($property) > 64 ? substr($property, 0, 64) : $property;
            // Remove . from column names, since it may be confused with a table-qualified name.
            $column_name = str_replace('.', ':', $column_name);
            $propertyToColumnNameMapping[$property] = $column_name;
        }
        return $propertyToColumnNameMapping;
    }

    private function removeDisruptiveStatsColumns($columns)
    {
        unset($columns["potential_hits"]);
        unset($columns["detector"]);
        return $columns;
    }

    private function removeDisruptiveFindingsColumns($columns)
    {
        unset($columns["id"]);
        unset($columns["target_snippets"]);
        return $columns;
    }

    private function addColumnToTable($table_name, $column)
    {
        Schema::table($table_name, function ($table) use ($column) {
            $table->text($column)->nullable();
        });
    }

    private function storeFindings(Detector $detector, Experiment $experiment, $projectId, $versionId, $misuseId, $runId, $findings)
    {
        $misuse = $this->createMisuse($detector, $experiment, $projectId, $versionId, $misuseId, $runId);
        foreach ($findings as $finding) {
            $this->storeFinding($detector, $experiment, $projectId, $versionId, $misuseId, $misuse, $finding);
            if ($experiment->id === 2) {
                $this->storeFindingTargetSnippets($projectId, $versionId, $misuseId, $finding->{'rank'},
                    $finding->{'target_snippets'});
            }
        }
    }

    private function createMisuse(Detector $detector, Experiment $experiment, $projectId, $versionId, $misuseId, $runId)
    {
        if ($experiment->id == 1 || $experiment->id == 3) {
            $metadata = Metadata::where(['project_muid' => $projectId, 'version_muid' => $versionId, 'misuse_muid' => $misuseId])->first();
            if($metadata){
                $misuse = Misuse::create(['metadata_id' => $metadata->id, 'misuse_muid' => $misuseId, 'run_id' => $runId, 'detector_id' => $detector->id]);
            } else {
                $misuse = Misuse::create(['misuse_muid' => $misuseId, 'run_id' => $runId, 'detector_id' => $detector->id]);
            }
        } else {
            $misuse = Misuse::create(['misuse_muid' => $misuseId, 'run_id' => $runId, 'detector_id' => $detector->id]);
        }
        return $misuse;
    }

    private function storeFinding(Detector $detector, $experiment, $projectId, $versionId, $misuseId, $misuse, $finding)
    {
        $values = array('project_muid' => $projectId, 'version_muid' => $versionId, 'misuse_muid' => $misuseId, 'misuse_id' => $misuse->id, 'experiment_id' => $experiment->id);
        $propertyToColumnNameMapping = $this->getPropertyToColumnNameMapping([$finding]);
        $propertyToColumnNameMapping = $this->removeDisruptiveFindingsColumns($propertyToColumnNameMapping);
        foreach ($propertyToColumnNameMapping as $property => $column) {
            $value = $finding->{$property};
            $values[$column] = $value;
        }
        $finding = new Finding;
        $finding->setDetector($detector);
        foreach($values as $key => $value){
            $finding[$key] = $value;
        }
        $finding->save();
    }

    private function updateRun(Detector $detector, Experiment $experiment, $projectId, $versionId, $run)
    {
        $savedRun = Run::of($detector)->in($experiment)->where(['project_muid' => $projectId, 'version_muid' => $versionId])->first();
        if (!$savedRun) {
            $savedRun = new \MuBench\ReviewSite\Models\Run;
            $savedRun->setDetector($detector);
            $savedRun->experiment_id = $experiment->id;
            $savedRun->project_muid = $projectId;
            $savedRun->version_muid = $versionId;
        }
        $propertyToColumnNameMapping = $this->getColumnNamesFromProperties($run);
        $propertyToColumnNameMapping = $this->removeDisruptiveStatsColumns($propertyToColumnNameMapping);
        foreach ($propertyToColumnNameMapping as $property => $column) {
            $value = $run->{$property};
            $savedRun[$column] = $value;
        }
        $savedRun->save();
    }

    private function storeFindingTargetSnippets($projectId, $versionId, $misuseId, $rank, $snippets)
    {
        foreach ($snippets as $snippet) {
            SnippetController::createSnippet($projectId, $versionId, $misuseId, $snippet->{'code'}, $snippet->{'first_line_number'});
        }
    }

}
