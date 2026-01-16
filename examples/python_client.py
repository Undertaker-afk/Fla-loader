#!/usr/bin/env python3
"""
Example Python script demonstrating how to use the Fla-loader API
from an external application (e.g., a game loader).

Requirements:
    pip install requests
"""

import requests
import json
import os
from typing import Optional, Dict, List

class FlaLoaderClient:
    """Client for interacting with the Fla-loader API"""
    
    def __init__(self, base_url: str):
        """
        Initialize the client
        
        Args:
            base_url: Base URL of the Flarum forum (e.g., https://forum.example.com)
        """
        self.base_url = base_url.rstrip('/')
        self.api_url = f"{self.base_url}/api"
        self.token: Optional[str] = None
        self.user_data: Optional[Dict] = None
        self.user_groups: List[Dict] = []
    
    def login(self, username: str, password: str, hwid: str) -> bool:
        """
        Login and obtain a session token
        
        Args:
            username: Username or email
            password: User password
            hwid: Hardware ID (unique identifier for the device)
            
        Returns:
            True if login successful, False otherwise
        """
        url = f"{self.api_url}/fla-loader/login"
        payload = {
            "username": username,
            "password": password,
            "hwid": hwid
        }
        
        try:
            response = requests.post(url, json=payload)
            
            if response.status_code == 200:
                data = response.json()['data']
                self.token = data['token']
                self.user_data = data['user']
                self.user_groups = data['groups']
                print(f"✓ Login successful! Welcome, {self.user_data['displayName']}")
                print(f"✓ Session token obtained (expires: {data['expiresAt']})")
                print(f"✓ User groups: {', '.join([g['name'] for g in self.user_groups])}")
                return True
            else:
                error = response.json().get('errors', [{}])[0]
                print(f"✗ Login failed: {error.get('detail', 'Unknown error')}")
                return False
                
        except requests.exceptions.RequestException as e:
            print(f"✗ Connection error: {e}")
            return False
    
    def has_group(self, group_name: str) -> bool:
        """
        Check if user has a specific group
        
        Args:
            group_name: Name of the group to check
            
        Returns:
            True if user has the group, False otherwise
        """
        return any(g['name'].lower() == group_name.lower() for g in self.user_groups)
    
    def download_file(self, file_id: int, save_path: str) -> bool:
        """
        Download a file from the forum
        
        Args:
            file_id: ID of the file to download
            save_path: Local path to save the file
            
        Returns:
            True if download successful, False otherwise
        """
        if not self.token:
            print("✗ Not logged in. Please login first.")
            return False
        
        url = f"{self.api_url}/fla-loader/download/{file_id}"
        params = {"token": self.token}
        
        try:
            print(f"⬇ Downloading file {file_id}...")
            response = requests.get(url, params=params, stream=True)
            
            if response.status_code == 200:
                # Get filename from Content-Disposition header
                content_disposition = response.headers.get('Content-Disposition', '')
                filename = save_path
                
                if 'filename=' in content_disposition:
                    filename = content_disposition.split('filename=')[1].strip('"')
                    filename = os.path.join(os.path.dirname(save_path), filename)
                
                # Download with progress
                total_size = int(response.headers.get('content-length', 0))
                downloaded = 0
                
                with open(filename, 'wb') as f:
                    for chunk in response.iter_content(chunk_size=8192):
                        if chunk:
                            f.write(chunk)
                            downloaded += len(chunk)
                            if total_size > 0:
                                progress = (downloaded / total_size) * 100
                                print(f"\r⬇ Progress: {progress:.1f}%", end='', flush=True)
                
                print(f"\n✓ File downloaded successfully: {filename}")
                return True
            else:
                error = response.json().get('errors', [{}])[0]
                print(f"✗ Download failed: {error.get('detail', 'Unknown error')}")
                return False
                
        except requests.exceptions.RequestException as e:
            print(f"✗ Connection error: {e}")
            return False


def main():
    """Example usage"""
    
    # Configuration
    FORUM_URL = "https://forum.example.com"  # Change this to your forum URL
    USERNAME = "your_username"                 # Change this to your username
    PASSWORD = "your_password"                 # Change this to your password
    FILE_ID = 1                                # Change this to the file ID you want to download
    
    # Generate or retrieve HWID (in a real application, this should be a unique hardware identifier)
    import platform
    import hashlib
    HWID = hashlib.sha256(f"{platform.node()}-{platform.machine()}".encode()).hexdigest()
    
    print("=== Fla-loader API Client Example ===\n")
    
    # Create client
    client = FlaLoaderClient(FORUM_URL)
    
    # Login
    print("1. Logging in...")
    if not client.login(USERNAME, PASSWORD, HWID):
        print("\nLogin failed. Please check your credentials.")
        return
    
    print("\n2. Checking permissions...")
    # Check if user has required group (example)
    if client.has_group("VIP"):
        print("✓ User has VIP access")
    else:
        print("ℹ User does not have VIP access")
    
    # Download file
    print("\n3. Downloading file...")
    save_path = "./downloads/resource_pack.zip"
    os.makedirs("./downloads", exist_ok=True)
    
    if client.download_file(FILE_ID, save_path):
        print("\n✓ All operations completed successfully!")
    else:
        print("\n✗ Download failed.")


if __name__ == "__main__":
    main()
