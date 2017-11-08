<?php
namespace MuBench\ReviewSite;


class CSVHelper
{

    public static function exportStatistics($experiment, $stats)
    {
        $rows = [];
        foreach ($stats as $stat) {
            $row = [];
            $row["detector"] = $stat->getDisplayName();
            $row["project"] = $stat->number_of_projects;

            if ($experiment->id === 1) {
                $row["synthetics"] = $stat->number_of_synthetics;
            }
            if ($experiment->id === 1 || $experiment->id === 3) {
                $row["misuses"] = $stat->number_of_misuses;
            }

            $row["potential_hits"] = $stat->misuses_to_review;
            $row["open_reviews"] = $stat->open_reviews;
            $row["need_clarification"] = $stat->number_of_needs_clarification;
            $row["yes_agreements"] = $stat->yes_agreements;
            $row["no_agreements"] = $stat->no_agreements;
            $row["total_agreements"] = $stat->getNumberOfAgreements();
            $row["yes_no_agreements"] = $stat->yes_no_disagreements;
            $row["no_yes_agreements"] = $stat->no_yes_disagreements;
            $row["total_disagreements"] = $stat->getNumberOfDisagreements();
            $row["kappa_p0"] = $stat->getKappaP0();
            $row["kappa_pe"] = $stat->getKappaPe();
            $row["kappa_score"] = $stat->getKappaScore();
            $row["hits"] = $stat->number_of_hits;

            if ($experiment->id === 2) {
                $row["precision"] = $stat->getPrecision();
            } else {
                $row["recall"] = $stat->getRecall();
            }

            $rows[] = $row;
        }
        return CSVHelper::createCSV($rows);
    }

    public static function exportRunStatistics($runs)
    {
        $rows = [];
        foreach ($runs as $run) {
            $run_details = [];
            $run_details["project"] = $run->project_muid;
            $run_details["version"] = $run->version_muid;
            $run_details["result"] = $run->result;
            $run_details["number_of_findings"] = $run->number_of_findings;
            $run_details["runtime"] = $run->runtime;

            foreach ($run->misuses as $misuse) {
                $row = $run_details;

                $row["misuse"] = $misuse->misuse_muid;
                $row["decision"] = $misuse->getReviewState();
                if ($misuse->hasResolutionReview()) {
                    $resolution = $misuse->getResolutionReview();
                    $row["resolution_decision"] = $resolution->getDecision();
                    $row["resolution_comment"] = CSVHelper::escapeText($resolution->comment);
                } else {
                    $row["resolution_decision"] = "";
                    $row["resolution_comment"] = "";
                }

                $reviews = $misuse->getReviews();
                $review_index = 0;
                foreach ($reviews as $review) {
                    $review_index++;
                    $row["review{$review_index}_name"] = $review->reviewer->name;
                    $row["review{$review_index}_decision"] = $review->getDecision();
                    $row["review{$review_index}_comment"] = CSVHelper::escapeText($review->comment);
                }

                $rows[] = $row;
            }
            if (empty($run['misuses'])) {
                $rows[] = $run_details;
            }
        }
        return CSVHelper::createCSV($rows);
    }


    private static function createCSV($rows)
    {
        $lines = [];
        $header = [];
        foreach ($rows as $line) {
            $lines[] = implode(',', $line);

            $columns = array_keys($line);
            if(count($columns) > count($header)){
                $header = $columns;
            }
        }
        array_unshift($lines, implode(',', $header));
        array_unshift($lines, "sep=,");
        return implode("\n", $lines);
    }

    private static function escapeText($text){
        return "\"" . $text . "\"";
    }

}
