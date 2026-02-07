# Integration Examples

This document provides code examples for integrating with the TrustChain API.

## Table of Contents
- [PHP Integration](#php-integration)
- [JavaScript/Node.js Integration](#javascriptnodejs-integration)
- [Python Integration](#python-integration)
- [cURL Examples](#curl-examples)

---

## PHP Integration

### Using Guzzle HTTP Client

```php
<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class TrustChainClient
{
    private $client;
    private $baseUrl;
    private $apiToken;

    public function __construct(string $baseUrl, string $apiToken)
    {
        $this->baseUrl = $baseUrl;
        $this->apiToken = $apiToken;
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiToken,
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Register a document on the blockchain
     */
    public function registerDocument(
        string $filePath,
        string $holderName,
        string $holderEmail,
        string $title,
        string $documentType,
        array $metadata = []
    ): array {
        try {
            $response = $this->client->post('/api/documents', [
                'multipart' => [
                    [
                        'name' => 'document',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
                    ],
                    ['name' => 'holder_name', 'contents' => $holderName],
                    ['name' => 'holder_email', 'contents' => $holderEmail],
                    ['name' => 'title', 'contents' => $title],
                    ['name' => 'document_type', 'contents' => $documentType],
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify a document by file
     */
    public function verifyDocument(string $filePath): array
    {
        try {
            $response = $this->client->post('/api/documents/verify', [
                'multipart' => [
                    [
                        'name' => 'document',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
                    ],
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify a document by document ID
     */
    public function verifyByDocumentId(string $documentId): array
    {
        try {
            $response = $this->client->post('/api/documents/verify', [
                'json' => ['document_id' => $documentId],
            ]);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get document details
     */
    public function getDocument(string $uuid): array
    {
        try {
            $response = $this->client->get("/api/documents/{$uuid}");
            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Revoke a document
     */
    public function revokeDocument(string $uuid, string $reason): array
    {
        try {
            $response = $this->client->post("/api/documents/{$uuid}/revoke", [
                'json' => ['reason' => $reason],
            ]);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

// Usage Example
$client = new TrustChainClient('http://localhost:8000', 'your-api-token');

// Register a document
$result = $client->registerDocument(
    '/path/to/certificate.pdf',
    'John Doe',
    'john@example.com',
    'Bachelor Degree Certificate',
    'certificate',
    ['institution_name' => 'MIT']
);

if ($result['success']) {
    echo "Document registered: " . $result['data']['document_uuid'] . "\n";
    echo "Transaction hash: " . $result['data']['blockchain_tx_hash'] . "\n";
}

// Verify a document
$verification = $client->verifyDocument('/path/to/certificate.pdf');

if ($verification['success'] && $verification['status'] === 'valid') {
    echo "Document is valid!\n";
    echo "Issued by: " . $verification['data']['issuer']['name'] . "\n";
}
```

---

## JavaScript/Node.js Integration

### Using Axios

```javascript
const axios = require('axios');
const FormData = require('form-data');
const fs = require('fs');

class TrustChainClient {
    constructor(baseUrl, apiToken) {
        this.baseUrl = baseUrl;
        this.client = axios.create({
            baseURL: baseUrl,
            headers: {
                'Authorization': `Bearer ${apiToken}`,
                'Accept': 'application/json'
            }
        });
    }

    /**
     * Register a document on the blockchain
     */
    async registerDocument(filePath, holderName, holderEmail, title, documentType, metadata = {}) {
        try {
            const formData = new FormData();
            formData.append('document', fs.createReadStream(filePath));
            formData.append('holder_name', holderName);
            formData.append('holder_email', holderEmail);
            formData.append('title', title);
            formData.append('document_type', documentType);

            // Add metadata
            Object.keys(metadata).forEach(key => {
                formData.append(`metadata[${key}]`, metadata[key]);
            });

            const response = await this.client.post('/api/documents', formData, {
                headers: formData.getHeaders()
            });

            return response.data;
        } catch (error) {
            return {
                success: false,
                error: error.response?.data?.message || error.message
            };
        }
    }

    /**
     * Verify a document by file
     */
    async verifyDocument(filePath) {
        try {
            const formData = new FormData();
            formData.append('document', fs.createReadStream(filePath));

            const response = await this.client.post('/api/documents/verify', formData, {
                headers: formData.getHeaders()
            });

            return response.data;
        } catch (error) {
            return {
                success: false,
                error: error.response?.data?.message || error.message
            };
        }
    }

    /**
     * Verify a document by document ID
     */
    async verifyByDocumentId(documentId) {
        try {
            const response = await this.client.post('/api/documents/verify', {
                document_id: documentId
            });

            return response.data;
        } catch (error) {
            return {
                success: false,
                error: error.response?.data?.message || error.message
            };
        }
    }

    /**
     * Get document details
     */
    async getDocument(uuid) {
        try {
            const response = await this.client.get(`/api/documents/${uuid}`);
            return response.data;
        } catch (error) {
            return {
                success: false,
                error: error.response?.data?.message || error.message
            };
        }
    }

    /**
     * Revoke a document
     */
    async revokeDocument(uuid, reason) {
        try {
            const response = await this.client.post(`/api/documents/${uuid}/revoke`, {
                reason: reason
            });

            return response.data;
        } catch (error) {
            return {
                success: false,
                error: error.response?.data?.message || error.message
            };
        }
    }
}

// Usage Example
(async () => {
    const client = new TrustChainClient('http://localhost:8000', 'your-api-token');

    // Register a document
    const result = await client.registerDocument(
        './certificate.pdf',
        'John Doe',
        'john@example.com',
        'Bachelor Degree Certificate',
        'certificate',
        { institution_name: 'MIT' }
    );

    if (result.success) {
        console.log('Document registered:', result.data.document_uuid);
        console.log('Transaction hash:', result.data.blockchain_tx_hash);
    }

    // Verify a document
    const verification = await client.verifyDocument('./certificate.pdf');

    if (verification.success && verification.status === 'valid') {
        console.log('Document is valid!');
        console.log('Issued by:', verification.data.issuer.name);
    }
})();
```

---

## Python Integration

### Using Requests Library

```python
import requests
from pathlib import Path

class TrustChainClient:
    def __init__(self, base_url: str, api_token: str):
        self.base_url = base_url
        self.headers = {
            'Authorization': f'Bearer {api_token}',
            'Accept': 'application/json'
        }
    
    def register_document(self, file_path: str, holder_name: str, 
                         holder_email: str, title: str, 
                         document_type: str, metadata: dict = None) -> dict:
        """Register a document on the blockchain"""
        try:
            with open(file_path, 'rb') as f:
                files = {'document': (Path(file_path).name, f, 'application/pdf')}
                
                data = {
                    'holder_name': holder_name,
                    'holder_email': holder_email,
                    'title': title,
                    'document_type': document_type
                }
                
                # Add metadata if provided
                if metadata:
                    for key, value in metadata.items():
                        data[f'metadata[{key}]'] = value
                
                response = requests.post(
                    f'{self.base_url}/api/documents',
                    headers=self.headers,
                    files=files,
                    data=data
                )
                
                return response.json()
        except Exception as e:
            return {'success': False, 'error': str(e)}
    
    def verify_document(self, file_path: str = None, 
                       document_id: str = None, 
                       verification_code: str = None) -> dict:
        """Verify a document"""
        try:
            if file_path:
                with open(file_path, 'rb') as f:
                    files = {'document': (Path(file_path).name, f, 'application/pdf')}
                    response = requests.post(
                        f'{self.base_url}/api/documents/verify',
                        files=files
                    )
            elif document_id:
                response = requests.post(
                    f'{self.base_url}/api/documents/verify',
                    json={'document_id': document_id}
                )
            elif verification_code:
                response = requests.post(
                    f'{self.base_url}/api/documents/verify',
                    json={'verification_code': verification_code}
                )
            else:
                return {'success': False, 'error': 'No verification method provided'}
            
            return response.json()
        except Exception as e:
            return {'success': False, 'error': str(e)}
    
    def get_document(self, uuid: str) -> dict:
        """Get document details"""
        try:
            response = requests.get(
                f'{self.base_url}/api/documents/{uuid}',
                headers=self.headers
            )
            return response.json()
        except Exception as e:
            return {'success': False, 'error': str(e)}
    
    def revoke_document(self, uuid: str, reason: str) -> dict:
        """Revoke a document"""
        try:
            response = requests.post(
                f'{self.base_url}/api/documents/{uuid}/revoke',
                headers=self.headers,
                json={'reason': reason}
            )
            return response.json()
        except Exception as e:
            return {'success': False, 'error': str(e)}

# Usage Example
if __name__ == '__main__':
    client = TrustChainClient('http://localhost:8000', 'your-api-token')
    
    # Register a document
    result = client.register_document(
        file_path='./certificate.pdf',
        holder_name='John Doe',
        holder_email='john@example.com',
        title='Bachelor Degree Certificate',
        document_type='certificate',
        metadata={'institution_name': 'MIT'}
    )
    
    if result.get('success'):
        print(f"Document registered: {result['data']['document_uuid']}")
        print(f"Transaction hash: {result['data']['blockchain_tx_hash']}")
    
    # Verify a document
    verification = client.verify_document(file_path='./certificate.pdf')
    
    if verification.get('success') and verification.get('status') == 'valid':
        print('Document is valid!')
        print(f"Issued by: {verification['data']['issuer']['name']}")
```

---

## cURL Examples

### Register a Document

```bash
curl -X POST http://localhost:8000/api/documents \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -F "document=@/path/to/certificate.pdf" \
  -F "holder_name=John Doe" \
  -F "holder_email=john@example.com" \
  -F "title=Bachelor Degree Certificate" \
  -F "document_type=certificate" \
  -F "metadata[institution_name]=MIT" \
  -F "metadata[certificate_number]=CERT-2024-001"
```

### Verify Document by File

```bash
curl -X POST http://localhost:8000/api/documents/verify \
  -F "document=@/path/to/certificate.pdf"
```

### Verify Document by Document ID

```bash
curl -X POST http://localhost:8000/api/documents/verify \
  -H "Content-Type: application/json" \
  -d '{
    "document_id": "0x1234567890abcdef..."
  }'
```

### Verify Document by Verification Code

```bash
curl -X POST http://localhost:8000/api/documents/verify \
  -H "Content-Type: application/json" \
  -d '{
    "verification_code": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

### Get Document Details

```bash
curl -X GET http://localhost:8000/api/documents/550e8400-e29b-41d4-a716-446655440000 \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### List All Documents

```bash
curl -X GET http://localhost:8000/api/documents \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Revoke Document

```bash
curl -X POST http://localhost:8000/api/documents/550e8400-e29b-41d4-a716-446655440000/revoke \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Information correction required"
  }'
```

### Check Transaction Status

```bash
curl -X GET http://localhost:8000/api/documents/550e8400-e29b-41d4-a716-446655440000/status \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

---

## Error Handling

All API responses follow a consistent format:

**Success Response:**
```json
{
  "success": true,
  "data": { ... }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error (only in debug mode)"
}
```

**Validation Error Response:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "Validation error message"
    ]
  }
}
```

---

## Webhooks (Future Feature)

Coming soon: Real-time notifications for:
- Document verification attempts
- Blockchain confirmation status
- Document revocation events

---

For more information, see the [API Documentation](API_DOCUMENTATION.md).
