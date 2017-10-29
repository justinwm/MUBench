<?php

namespace MuBench\ReviewSite\Controller;

use Monolog\Logger;
use MuBench\ReviewSite\DBConnection;
use MuBench\ReviewSite\Model\Detector;
use MuBench\ReviewSite\Models\Metadata;
use MuBench\ReviewSite\Models\Pattern;
use MuBench\ReviewSite\Models\Type;
use Slim\Http\Request;
use Slim\Http\Response;

class MetadataController extends Controller
{

    public function update(Request $request, Response $response, array $args)
    {
        $metadata = decodeJsonBody($request);
        if (!$metadata) {
            return error_response($response, $this->logger, 400, 'empty: ' . print_r($request->getBody(), true));
        }
        foreach ($metadata as $misuseMetadata) {
            $projectId = $misuseMetadata['project'];
            $versionId = $misuseMetadata['version'];
            $misuseId = $misuseMetadata['misuse'];
            $description = $misuseMetadata['description'];
            $fix = $misuseMetadata['fix'];
            $location = $misuseMetadata['location'];
            $violationTypes = $misuseMetadata['violation_types'];
            $patterns = $misuseMetadata['patterns'];
            $targetSnippets = $misuseMetadata['target_snippets'];

            $this->updateMetadata($projectId, $versionId, $misuseId, $description, $fix, $location, $violationTypes, $patterns, $targetSnippets);
        }
        return $response->withStatus(200);
    }

    function updateMetadata($projectId, $versionId, $misuseId, $description, $fix, $location, $violationTypes, $patterns, $targetSnippets)
    {
        $metadata = $this->saveMetadata($projectId, $versionId, $misuseId, $description, $fix, $location);
        $this->saveViolationTypes($metadata->id, $violationTypes);
        $this->savePatterns($metadata->id, $patterns);
        $this->saveTargetSnippets($misuseId, $projectId, $versionId, $targetSnippets);
    }

    private function saveMetadata($projectId, $versionId, $misuseId, $description, $fix, $location)
    {
        return Metadata::firstOrCreate(['project_muid' => $projectId, 'version_muid' => $versionId, 'misuse_muid' => $misuseId,
            'description' => $description, 'fix_description' => $fix['description'],
            'diff_url' => $fix['diff-url'], 'file' => $location['file'], 'method' => $location['method']]);
    }

    private function saveViolationTypes($metadataId, $violationTypes)
    {
        foreach ($violationTypes as $type_name) {
            $violation_type = Type::firstOrCreate(['name' => $type_name]);
            $this->database2->table('metadata_types')->insert(array('metadata_id' => $metadataId, 'type_id' => $violation_type->id));
        }
    }

    private function savePatterns($metadataId, $patterns)
    {
        // TODO: DELETE PATTERTNS ?
        if ($patterns) {
            foreach ($patterns as $pattern) {
                Pattern::firstOrCreate(['metadata_id' => $metadataId, 'code' => $pattern['snippet']['code'], 'line' => $pattern['snippet']['first_line']]);
            }
        }
    }

    private function saveTargetSnippets($misuseId, $projectId, $versionId, $targetSnippets)
    {
        // TODO: ???
       /* if ($targetSnippets) {
            foreach ($targetSnippets as $snippet) {
                $this->db->table('meta_snippets')->insert(['project' => $projectId, 'version' => $versionId,
                    'misuse' => $misuseId, 'snippet' => $snippet['code'], 'line' => $snippet['first_line_number']]);
            }
        }*/
    }
}
