#!/usr/bin/env python
"""
Adapter Registry & Auto-Detection for App Tutorial Video Skill.
"""

from typing import Dict, List, Optional, Tuple
from .base_adapter import BaseAdapter
from .laravel_adapter import LaravelAdapter
from .react_vite_adapter import ReactViteAdapter
from .generic_web_adapter import GenericWebAdapter


class AdapterRegistry:
    """Registry managing available project adapters."""

    def __init__(self):
        self.adapters: List[BaseAdapter] = [
            LaravelAdapter(),
            ReactViteAdapter(),
            GenericWebAdapter(),
        ]

    def register(self, adapter: BaseAdapter):
        self.adapters.insert(0, adapter)

    def detect(self, workspace_root: str) -> Tuple[BaseAdapter, Dict]:
        """
        Scan workspace root and return highest confidence adapter + detection metadata.
        """
        best_adapter = None
        best_meta = None
        highest_conf = -1.0

        for adapter in self.adapters:
            meta = adapter.detect(workspace_root)
            if meta:
                conf = meta.get("confidence", 0.0)
                if conf > highest_conf:
                    highest_conf = conf
                    best_adapter = adapter
                    best_meta = meta

        if best_adapter and best_meta:
            return best_adapter, best_meta

        # Fallback to generic web adapter
        fallback = GenericWebAdapter()
        return fallback, {
            "framework": "generic_web",
            "confidence": 0.1,
            "entry_points": [],
            "server_recommendation": None,
            "default_port": None,
            "details": {}
        }

    def get_by_name(self, name: str) -> Optional[BaseAdapter]:
        for a in self.adapters:
            if a.name == name or a.framework.lower() == name.lower():
                return a
        return None


__all__ = [
    "BaseAdapter",
    "LaravelAdapter",
    "ReactViteAdapter",
    "GenericWebAdapter",
    "AdapterRegistry",
]
