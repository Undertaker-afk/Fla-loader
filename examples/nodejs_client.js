#!/usr/bin/env node
/**
 * Example Node.js script demonstrating how to use the Fla-loader API
 * from an external application (e.g., a game loader).
 * 
 * Requirements:
 *     npm install axios
 */

const axios = require('axios');
const fs = require('fs');
const path = require('path');

class FlaLoaderClient {
    /**
     * Initialize the client
     * @param {string} baseUrl - Base URL of the Flarum forum
     */
    constructor(baseUrl) {
        this.baseUrl = baseUrl.replace(/\/$/, '');
        this.apiUrl = `${this.baseUrl}/api`;
        this.token = null;
        this.userData = null;
        this.userGroups = [];
    }

    /**
     * Login and obtain a session token
     * @param {string} username - Username or email
     * @param {string} password - User password
     * @returns {Promise<boolean>} True if login successful
     */
    async login(username, password) {
        const url = `${this.apiUrl}/fla-loader/login`;
        const payload = { username, password };

        try {
            const response = await axios.post(url, payload);
            
            if (response.status === 200) {
                const data = response.data.data;
                this.token = data.token;
                this.userData = data.user;
                this.userGroups = data.groups;
                
                console.log(`✓ Login successful! Welcome, ${this.userData.displayName}`);
                console.log(`✓ Session token obtained (expires: ${data.expiresAt})`);
                console.log(`✓ User groups: ${this.userGroups.map(g => g.name).join(', ')}`);
                return true;
            }
        } catch (error) {
            if (error.response) {
                const errorDetail = error.response.data.errors?.[0]?.detail || 'Unknown error';
                console.log(`✗ Login failed: ${errorDetail}`);
            } else {
                console.log(`✗ Connection error: ${error.message}`);
            }
            return false;
        }
    }

    /**
     * Check if user has a specific group
     * @param {string} groupName - Name of the group to check
     * @returns {boolean} True if user has the group
     */
    hasGroup(groupName) {
        return this.userGroups.some(g => 
            g.name.toLowerCase() === groupName.toLowerCase()
        );
    }

    /**
     * Download a file from the forum
     * @param {number} fileId - ID of the file to download
     * @param {string} savePath - Local path to save the file
     * @returns {Promise<boolean>} True if download successful
     */
    async downloadFile(fileId, savePath) {
        if (!this.token) {
            console.log('✗ Not logged in. Please login first.');
            return false;
        }

        const url = `${this.apiUrl}/fla-loader/download/${fileId}`;
        const params = { token: this.token };

        try {
            console.log(`⬇ Downloading file ${fileId}...`);
            
            const response = await axios.get(url, {
                params,
                responseType: 'stream'
            });

            if (response.status === 200) {
                // Get filename from Content-Disposition header
                const contentDisposition = response.headers['content-disposition'] || '';
                let filename = savePath;
                
                if (contentDisposition.includes('filename=')) {
                    const match = contentDisposition.match(/filename="(.+)"/);
                    if (match) {
                        filename = path.join(path.dirname(savePath), match[1]);
                    }
                }

                // Ensure directory exists
                const dir = path.dirname(filename);
                if (!fs.existsSync(dir)) {
                    fs.mkdirSync(dir, { recursive: true });
                }

                // Download with progress
                const totalSize = parseInt(response.headers['content-length'] || 0);
                let downloaded = 0;

                const writer = fs.createWriteStream(filename);
                
                response.data.on('data', (chunk) => {
                    downloaded += chunk.length;
                    if (totalSize > 0) {
                        const progress = (downloaded / totalSize) * 100;
                        process.stdout.write(`\r⬇ Progress: ${progress.toFixed(1)}%`);
                    }
                });

                return new Promise((resolve, reject) => {
                    writer.on('finish', () => {
                        console.log(`\n✓ File downloaded successfully: ${filename}`);
                        resolve(true);
                    });
                    writer.on('error', (err) => {
                        console.log(`\n✗ Write error: ${err.message}`);
                        reject(false);
                    });
                    response.data.pipe(writer);
                });
            }
        } catch (error) {
            if (error.response) {
                const errorDetail = error.response.data.errors?.[0]?.detail || 'Unknown error';
                console.log(`✗ Download failed: ${errorDetail}`);
            } else {
                console.log(`✗ Connection error: ${error.message}`);
            }
            return false;
        }
    }
}

async function main() {
    // Configuration
    const FORUM_URL = 'https://forum.example.com';  // Change this to your forum URL
    const USERNAME = 'your_username';                 // Change this to your username
    const PASSWORD = 'your_password';                 // Change this to your password
    const FILE_ID = 1;                                // Change this to the file ID

    console.log('=== Fla-loader API Client Example ===\n');

    // Create client
    const client = new FlaLoaderClient(FORUM_URL);

    // Login
    console.log('1. Logging in...');
    if (!await client.login(USERNAME, PASSWORD)) {
        console.log('\nLogin failed. Please check your credentials.');
        return;
    }

    console.log('\n2. Checking permissions...');
    // Check if user has required group (example)
    if (client.hasGroup('VIP')) {
        console.log('✓ User has VIP access');
    } else {
        console.log('ℹ User does not have VIP access');
    }

    // Download file
    console.log('\n3. Downloading file...');
    const savePath = './downloads/resource_pack.zip';

    if (await client.downloadFile(FILE_ID, savePath)) {
        console.log('\n✓ All operations completed successfully!');
    } else {
        console.log('\n✗ Download failed.');
    }
}

// Run if executed directly
if (require.main === module) {
    main().catch(console.error);
}

module.exports = FlaLoaderClient;
