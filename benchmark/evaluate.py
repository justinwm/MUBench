from genericpath import isdir, exists, isfile, getsize
from os import listdir
from os.path import join, splitext
from os.path import normpath, basename
from typing import Dict, Tuple, Optional, Any

from benchmark.utils.data_util import normalize_result_misuse_path, normalize_data_misuse_path
from benchmark.utils.dotgraph_util import get_labels_from_result_file, get_labels_from_data_content
from benchmark.utils.io import safe_open
from benchmark.utils.printing import subprocess_print


class Evaluation:
    def __init__(self,
                 results_path: str,
                 detector: str,
                 detector_result_file: str,
                 checkout_base_dir: str):
        self.results_path = results_path
        self.detector = detector
        self.detector_result_file = detector_result_file
        self.checkout_base_dir = checkout_base_dir

        self.results = []

    def evaluate(self, data_file: str, data_content: Dict[str, Any]) -> Tuple[str, Optional[int]]:

        dirs_results = [join(self.results_path, result_dir) for result_dir in listdir(self.results_path) if
                        isdir(join(self.results_path, result_dir)) and not result_dir == '_LOGS']

        subprocess_print("Evaluation : running... ", end='')

        for dir_result in dirs_results:
            is_result_for_file = splitext(basename(normpath(data_file)))[0] == basename(normpath(dir_result))

            error_log = join(dir_result, "error.log")
            errors_occurred = exists(error_log) and isfile(error_log) and getsize(error_log) > 0

            if is_result_for_file and not errors_occurred:

                with safe_open(join(self.results_path, "_LOGS", "result-evaluation.log"), 'a+') as log:
                    print("===========================================================", file=log)

                    result_file = join(dir_result, self.detector_result_file)
                    print("Evaluating result {} against data {}".format(result_file, data_file), file=log)

                    file_found = False
                    label_found = False

                    if exists(result_file):
                        file_found = Evaluation.__is_file_found(result_file, data_content, self.checkout_base_dir,
                                                                log)
                        label_found = Evaluation.__is_label_found(result_file, data_content, log)

                    if file_found and label_found:
                        print("potential hit", flush=True)
                        self.results.append((basename(data_file), 1))
                        return
                    else:
                        print("no hit", flush=True)
                        self.results.append((basename(data_file), 0))
                        return

        print("ignored (no available findings)", flush=True)
        self.results.append((basename(data_file), None))

    def output_results(self) -> None:
        if not self.results:
            return

        def to_data_name(result: Tuple[str, int]) -> str:
            return result[0]

        def to_success(result: Tuple[str, int]) -> int:
            return result[1]

        applied_results = [result for result in self.results if result[1] is not None]
        total = len(self.results)
        applied = len(applied_results)
        found = sum(map(to_success, applied_results))

        def was_successful(result: Tuple[str, int]) -> bool:
            return result[1] is 1

        def was_not_successful(result: Tuple[str, int]) -> bool:
            return result[1] is 0

        def finished_with_error(result: Tuple[str, int]) -> bool:
            return result[1] is None

        found_misuses = map(to_data_name, filter(was_successful, self.results))
        not_found_misuses = map(to_data_name, filter(was_not_successful, self.results))
        misuses_with_errors = map(to_data_name, filter(finished_with_error, self.results))
        with safe_open(join(self.results_path, "Result.txt"), 'w+') as file_result:
            print('----------------------------------------------', file=file_result)
            print('Total number of misuses in the benchmark: ' + str(total), file=file_result)
            print('Number of analyzed misuses (might be less due to ignore or errors): ' + str(applied),
                  file=file_result)
            print('Number of misuses found: ' + str(found), file=file_result)
            print('----------------------------------------------', file=file_result)
            print('These misuses were found:', file=file_result)
            print('\n'.join(found_misuses), file=file_result)
            print('----------------------------------------------', file=file_result)
            print('These misuses were not found:', file=file_result)
            print('\n'.join(not_found_misuses), file=file_result)
            print('----------------------------------------------', file=file_result)
            print('These cases encountered an error (see logs for more information) or were ignored:',
                  file=file_result)
            print('\n'.join(misuses_with_errors), file=file_result)
            print('----------------------------------------------', file=file_result)

    @staticmethod
    def __is_file_found(result_file: str,
                        data_content: Dict[str, Any],
                        checkout_base_dir: str,
                        log_stream) -> bool:
        lines = [line.rstrip('\n') for line in safe_open(result_file, 'r')]

        for line in lines:
            if line.startswith("file: "):
                # cut file: from the line to get the path
                found_misuse = line[len("file: "):]
                normed_found_misuse = normalize_result_misuse_path(found_misuse, checkout_base_dir)

                for misuse_file in data_content["fix"]["files"]:
                    normed_misuse_file = normalize_data_misuse_path(misuse_file["name"])

                    print("{}: Comparing found misuse {}".format(normed_misuse_file, normed_found_misuse),
                          file=log_stream)

                    if normed_found_misuse == normed_misuse_file:
                        print("Match found!", file=log_stream)
                        return True
                    else:
                        print("No match", file=log_stream)

        return False

    @staticmethod
    def __is_label_found(result_file: str,
                         data_content: Dict[str, Any],
                         log_stream) -> bool:
        misuse_labels = get_labels_from_data_content(data_content)
        result_labels = get_labels_from_result_file(result_file)

        # don't check if no labels are given on any end
        if not misuse_labels or not result_labels:
            return True

        for result_label in result_labels:
            is_misuse_label = result_label in misuse_labels
            if is_misuse_label:
                print("Found correct label!", file=log_stream)
                return True

        print("Correct label was not found!", file=log_stream)
        return False