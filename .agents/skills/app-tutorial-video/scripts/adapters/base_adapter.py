#!/usr/bin/env python
"""
base_adapter.py — Base interface for Project Adapters in App Tutorial Video Skill.

Each adapter specializes in:
1. Framework & project structure detection.
2. Route, page, and navigation discovery.
3. Workflow mapping for tutorial planning.
4. Authentication setup generation.
"""

from abc import ABC, abstractmethod
from typing import Any, Dict, List, Optional


class BaseAdapter(ABC):
    """Abstract base class for project adapters."""

    def __init__(self, name: str, framework: str):
        self.name = name
        self.framework = framework

    @abstractmethod
    def detect(self, workspace_root: str) -> Optional[Dict[str, Any]]:
        """
        Inspect workspace and return detection metadata if matched, else None.
        Returns:
            {
                "framework": str,
                "confidence": float,
                "entry_points": list,
                "package_manifest": str or None,
                "server_recommendation": str or None,
                "default_port": int or None,
                "details": dict
            }
        """
        pass

    @abstractmethod
    def discover_pages(self, workspace_root: str, profile: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        """
        Discover accessible pages, routes, or views in the application.
        Returns list of:
            {
                "id": str,
                "title": str,
                "path_or_url": str,
                "source_file": str,
                "requires_auth": bool,
                "key_selectors": list
            }
        """
        pass

    @abstractmethod
    def discover_workflows(self, workspace_root: str, profile: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        """
        Discover actionable user journeys suitable for video tutorials.
        Returns list of:
            {
                "workflow_id": str,
                "name": str,
                "target_users": list,
                "description": str,
                "steps": list
            }
        """
        pass

    def generate_auth_setup(self, profile: Dict[str, Any]) -> List[Dict[str, Any]]:
        """
        Generate setup steps for authentication based on profile configuration.
        """
        auth_cfg = profile.get("auth", {})
        auth_type = auth_cfg.get("type", "none")
        if auth_type == "none" or not auth_type:
            return []

        setup_steps = []
        if auth_type == "form_login":
            login_url = auth_cfg.get("login_url")
            if login_url:
                setup_steps.append({"do": "goto", "url": login_url})
            email_sel = auth_cfg.get("email_selector", "#email")
            pass_sel = auth_cfg.get("password_selector", "#password")
            submit_sel = auth_cfg.get("submit_selector", "button[type='submit']")
            success_sel = auth_cfg.get("success_selector")

            # Reference env vars or safe profile defaults
            email_val = auth_cfg.get("email_value") or f"${{{auth_cfg.get('email_env', 'TUTORIAL_EMAIL')}}}"
            pass_val = auth_cfg.get("password_value") or f"${{{auth_cfg.get('password_env', 'TUTORIAL_PASSWORD')}}}"

            setup_steps.append({"do": "fill", "target": email_sel, "value": email_val})
            setup_steps.append({"do": "fill", "target": pass_sel, "value": pass_val})
            setup_steps.append({"do": "click", "target": submit_sel})
            if success_sel:
                setup_steps.append({"do": "wait_for", "selector": success_sel})

        return setup_steps
