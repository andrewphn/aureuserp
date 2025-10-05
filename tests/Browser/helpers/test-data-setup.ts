import type { APIRequestContext } from '@playwright/test';

/**
 * Test Data Setup Utilities
 *
 * Helper functions for creating, managing, and cleaning up test data
 * for browser tests.
 */

export interface TestUser {
  id: number;
  name: string;
  email: string;
  password: string;
  token?: string;
}

export interface TestDocument {
  id: number;
  title: string;
  filePath: string;
  uploadedBy: number;
}

export class TestDataManager {
  private createdResources: Map<string, number[]> = new Map();

  constructor(private request: APIRequestContext) {}

  /**
   * Create a test user
   */
  async createUser(data?: Partial<TestUser>): Promise<TestUser> {
    const userData = {
      name: data?.name || 'Test User',
      email: data?.email || `test-${Date.now()}@example.com`,
      password: data?.password || 'password123',
    };

    const response = await this.request.post('/api/users', {
      data: userData,
    });

    const result = await response.json();
    const user = { ...userData, id: result.data.id };

    this.trackResource('users', user.id);

    return user;
  }

  /**
   * Create a test PDF document
   */
  async createDocument(data?: Partial<TestDocument>): Promise<TestDocument> {
    const documentData = {
      title: data?.title || `Test Document ${Date.now()}`,
      description: 'Automated test document',
      file_path: data?.filePath || 'test-pdfs/sample.pdf',
      tags: ['test', 'automated'],
      is_public: true,
      uploaded_by: data?.uploadedBy,
    };

    const response = await this.request.post('/api/pdf-documents', {
      data: documentData,
    });

    const result = await response.json();
    const document: TestDocument = {
      id: result.data.id,
      title: result.data.attributes.title,
      filePath: result.data.attributes.file_path,
      uploadedBy: result.data.attributes.uploaded_by,
    };

    this.trackResource('documents', document.id);

    return document;
  }

  /**
   * Create multiple test documents
   */
  async createDocuments(count: number, data?: Partial<TestDocument>): Promise<TestDocument[]> {
    const documents: TestDocument[] = [];

    for (let i = 0; i < count; i++) {
      const doc = await this.createDocument({
        ...data,
        title: `${data?.title || 'Test Document'} ${i + 1}`,
      });
      documents.push(doc);
    }

    return documents;
  }

  /**
   * Create an annotation for a document
   */
  async createAnnotation(documentId: number, pageNumber: number = 1): Promise<any> {
    const annotationData = {
      page_number: pageNumber,
      annotation_type: 'highlight',
      annotation_data: {
        color: '#ffff00',
        position: {
          x: 100,
          y: 200,
          width: 150,
          height: 50,
        },
      },
    };

    const response = await this.request.post(`/api/pdf/${documentId}/annotations`, {
      data: annotationData,
    });

    const result = await response.json();
    this.trackResource('annotations', result.data.id);

    return result.data;
  }

  /**
   * Track created resource for cleanup
   */
  private trackResource(type: string, id: number): void {
    if (!this.createdResources.has(type)) {
      this.createdResources.set(type, []);
    }
    this.createdResources.get(type)!.push(id);
  }

  /**
   * Clean up all created test data
   */
  async cleanup(): Promise<void> {
    // Delete annotations
    const annotations = this.createdResources.get('annotations') || [];
    for (const id of annotations) {
      try {
        await this.request.delete(`/api/annotations/${id}`);
      } catch (error) {
        console.warn(`Failed to delete annotation ${id}:`, error);
      }
    }

    // Delete documents
    const documents = this.createdResources.get('documents') || [];
    for (const id of documents) {
      try {
        await this.request.delete(`/api/pdf-documents/${id}`);
      } catch (error) {
        console.warn(`Failed to delete document ${id}:`, error);
      }
    }

    // Delete users
    const users = this.createdResources.get('users') || [];
    for (const id of users) {
      try {
        await this.request.delete(`/api/users/${id}`);
      } catch (error) {
        console.warn(`Failed to delete user ${id}:`, error);
      }
    }

    // Clear tracking
    this.createdResources.clear();
  }

  /**
   * Seed sample PDF files for testing
   */
  async seedSamplePdfs(): Promise<string[]> {
    const samplePaths = [
      'test-pdfs/sample-1.pdf',
      'test-pdfs/sample-2.pdf',
      'test-pdfs/sample-multi-page.pdf',
    ];

    // In a real implementation, you might copy actual PDF files
    // to the storage directory here

    return samplePaths;
  }

  /**
   * Get authentication token for API requests
   */
  async getAuthToken(email: string, password: string): Promise<string> {
    const response = await this.request.post('/api/login', {
      data: { email, password },
    });

    const result = await response.json();
    return result.token;
  }
}

/**
 * Create a TestDataManager instance
 */
export function createTestDataManager(request: APIRequestContext): TestDataManager {
  return new TestDataManager(request);
}
