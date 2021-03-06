from enum import Enum
from typing import List, Set

from data.project import Project


class Response(Enum):
    ok = 0
    skip = 1


class ProjectTask:
    def __init__(self):
        self.black_list = []
        self.white_list = []

    @property
    def name(self):
        return type(self).__name__.lower()

    @staticmethod
    def ok():
        return []

    @staticmethod
    def skip(entity):
        return [entity.id]

    def start(self) -> None:
        pass

    def process_project(self, project: Project) -> List[str]:
        raise NotImplementedError

    def end(self) -> None:
        pass
