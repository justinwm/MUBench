from copy import deepcopy
from typing import List

from data.detector import Detector
from data.finding import Finding, SpecializedFinding
from data.misuse import Misuse


class FindingsFilter:
    def get_potential_hits(self, findings: List[Finding]) -> List[SpecializedFinding]:
        raise NotImplementedError()


def _to_potential_hit(misuse_id, finding: Finding):
    potential_hit = deepcopy(finding)
    potential_hit["misuse"] = misuse_id
    return potential_hit


class PotentialHits(FindingsFilter):
    def __init__(self, misuses: List[Misuse]):
        self.misuses = misuses

    def get_potential_hits(self, findings: List[Finding]):
        potential_hits = []
        for misuse in self.misuses:
            misuse_potential_hits = self._get_potential_hits(misuse, findings, False)
            if not misuse_potential_hits:
                misuse_potential_hits = self._get_potential_hits(misuse, findings, True)
            potential_hits.extend(misuse_potential_hits)
        return potential_hits

    @staticmethod
    def _get_potential_hits(misuse: Misuse, findings: List[Finding], method_name_only: bool):
        potential_hits = []
        for finding in findings:
            if finding.is_potential_hit(misuse, method_name_only):
                potential_hits.append(_to_potential_hit(misuse.misuse_id, finding))
        return potential_hits


class AllFindings(FindingsFilter):
    def __init__(self, limit: int = 0):
        self.limit = limit

    def get_potential_hits(self, findings: List[Finding]):
        top_findings = self.__get_top_findings(findings)
        potential_hits = []
        for rank, top_finding in enumerate(top_findings):
            potential_hits.append(_to_potential_hit("finding-{}".format(rank), top_finding))
        return potential_hits

    def __get_top_findings(self, findings):
        if self.limit:
            return findings[0:self.limit]
        else:
            return findings
