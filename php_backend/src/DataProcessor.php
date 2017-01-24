<?php

use Monolog\Logger;

class DataProcessor {

	private $db;
	private $logger;

	function __construct(DBConnection $db, Logger $logger){
		$this->db = $db;
		$this->logger = $logger;
	}

	public function getMetadata($misuse){
		$query = $this->db->getMetadata($misuse);
		foreach($query as $q){
			$data = $q;
			$data['violation_types'] = explode(";", $q['violation_types']);
			return $data;
		}
	}

	public function getReview($exp, $detector, $project, $version, $misuse, $reviewer){
		$query = $this->db->getReview($exp, $detector, $project, $version, $misuse, $reviewer);
		return $this->getReviewInfos($query['id'], $query);
	}

	public function getReviewInfos($id, $query){
	    $result = $query;
	    $findings = $this->db->getReviewFindings($id);
	    if(!$result || !$findings){
	        return [];
        }
        $result['hits'] = [];
	    foreach($findings as $finding){
	        $finding['types'] = $this->db->getReviewTypes(intval($finding['id']));
            $result['hits'][$finding['rank']] = $finding;
        }
        return $result;
    }

    public function getPatterns($misuse){
		$query = $this->db->getPattern($misuse);
		foreach($query as $q){
			yield $q;
		}
	}

	public function getHits($detector, $project, $version, $misuse, $exp){
	    $table = $this->db->getTableName($detector);
	    if(!$table){
	        return [];
        }
		$query = $this->db->getHits($table, $project, $version, $misuse, $exp);
		$result = [];
		foreach($query as $q){
			foreach($q as $key => $value){
				if($key !== "target_snippets" && strpos($value, ";") !== false){
					$q[$key] = explode(";", $value);
				}
			}
			$result[] = $q;
		}
        for($i = 0; $i < count($result); $i = $i + 1){
            ksort($result[$i]);
        }
		return $result;
	}

	public function getDetectors($exp){
		$detectors = $this->db->getDetectorsTables();
		$data = [];
		foreach($detectors as $detector){
		    $this->logger->info("DETECTOR: " . $detector['name']);
		    if($this->db->hasFindingForExp($exp, $detector['id'])){
                $data[] = $detector['name'];
            }
        }
        asort($data);
        return $data;
	}

	public function getReviewsMisuse($exp, $detector, $project, $version, $misuse){
		$query = $this->db->getReviewsByIdentifier($exp, $detector, $project, $version, $misuse);
		$reviewer = [];
		if(!$query){
			return [];
		}
		foreach($query as $q){
		    $add = true;
		    foreach($reviewer as $r){
		        if($r === $q['name']){
                    $add = false;
                }
            }
            if($add) {
                $reviewer[] = $q['name'];
            }
		}
		return $reviewer;
	}

	public function getAllReviews(){
	    $query = $this->db->getAllReviews();
        if(!$query){
            return [];
        }
        return $this->unwrapReviews($query);
    }

	public function getTodo($reviewer){
        $exp = ["ex1", "ex2", "ex2"];
        $reviewable = [];
        foreach($exp as $ex){
            foreach($this->getDetectors($ex) as $detector){
                $index = $this->getIndex($ex, $detector);
                foreach($index as $project => $versions){
                    foreach($versions as $version) {
                        foreach ($version['hits'] as $misuse){
                            $reviewers = $this->getReviewsMisuse($ex, $detector, $project, $version['version'], $misuse['misuse']);
                            $reviewer = [];
                            $isReviewed = false;
                            foreach($reviewers as $r){
                                if($r === $reviewer){
                                    $isReviewed = true;
                                }else{
                                    $reviewer[] = $r;
                                }
                            }
                            if(count($reviewers) < 2 && !$isReviewed){
                                $review = [];
                                $review['exp'] = $ex;
                                $review['detector'] = $detector;
                                $review['project'] = $project;
                                $review['version'] = $version['version'];
                                $review['misuse'] = $misuse['misuse'];
                                $review['reviewer'] = $reviewer;
                                $reviewable[substr($ex, 2)][] = $review;
                            }
                        }
                    }
                }
            }
        }
        return $reviewable;
    }

	public function getReviewsByReviewer($reviewer){
	    $query = $this->db->getReviewsByReviewer($reviewer);
        if(!$query){
            return [];
        }
        return $this->unwrapReviews($query);
    }

    public function unwrapReviews($query){
	    $reviews = [];
        foreach($query as $q){
            $result = $this->getReviewInfos($q['id'], $q);
            $review = [];
            $review['types'] = [];
            $review['exp'] = $result['exp'];
            $review['detector'] = $result['detector'];
            $review['project'] = $result['project'];
            $review['version'] = $result['version'];
            $review['comment'] = $result['comment'];
            $review['misuse'] = $result['misuse'];
            foreach($result['hits'] as $hit){
                $review['decision'] = $hit['decision'];
                $review['types'] = $hit['types'];
            }
            $review['name'] = $result['name'];
            $review['reviewer'] = $this->getReviewsMisuse($review['exp'], $review['detector'], $review['project'], $review['version'], $review['misuse']);
            $reviews[strval(substr($review['exp'],2))][] = $review;
        }
        ksort($reviews);
        return $reviews;
    }

    public function dump($var){
        ob_start();
        var_dump($var);
        $result = ob_get_clean();
        return $result;
    }

	public function getIndex($exp, $detector){
	    $table = $this->db->getDetectorTable($detector);
		$stats = $this->db->getAllStats($table);
		$projects = [];
		foreach($stats as $s){
		    $s['hits'] = [];
            foreach($this->db->getPotentialHits($table, $exp, $s['project'], $s['version']) as $hit){
				if($exp !== "ex2"){
					$meta = $this->getMetadata($hit['misuse']);
					$hit['violation_types'] = $meta['violation_types'];
				}
				$reviews = $this->getReviewsMisuse($exp, $detector, $s['project'], $s['version'], $hit['misuse']);
				$hit['reviews'] = $reviews;
				$add = true;
                $id = $hit['misuse'];
				if(array_key_exists('hits', $s)) {
                    foreach ($s['hits'] as $h) {
                        if ($hit['misuse'] === $h['misuse']) {
                            $add = false;
                        }
                    }
                }
                if ($add) {
                    $s['hits'][$id] = $hit;
                }
			}
			$projects[$s['project']][$s['version']] = $s;
		}
		ksort($projects);
        foreach($projects as $key => $value){
            ksort($projects[$key]);
            foreach($projects[$key] as $k2 => $value){
                if($projects[$key][$k2] && array_key_exists('hits', $projects[$key][$k2])) {
                    ksort($projects[$key][$k2]['hits']);
                }
            }
        }

		return $projects;
	}

}