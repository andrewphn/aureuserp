/**
 * Product Category Tools - CRUD operations for product categories
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const productCategoryTools: Tool[] = [
  {
    name: 'list_product_categories',
    description: 'List product categories with filters (parent, search).',
    inputSchema: {
      type: 'object',
      properties: {
        parent_id: { type: 'number', description: 'Filter by parent category ID' },
        search: { type: 'string', description: 'Search by category name' },
        page: { type: 'number', description: 'Page number for pagination' },
        per_page: { type: 'number', description: 'Items per page (max 100)' },
      },
    },
  },
  {
    name: 'get_product_categories_tree',
    description: 'Get the full product category tree hierarchy.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'get_product_category',
    description: 'Get product category details by ID.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Category ID' },
      },
      required: ['id'],
    },
  },
  {
    name: 'create_product_category',
    description: 'Create a new product category.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Category name' },
        parent_id: { type: 'number', description: 'Parent category ID for subcategories' },
        description: { type: 'string', description: 'Category description' },
        sequence: { type: 'number', description: 'Display sequence/order' },
      },
      required: ['name'],
    },
  },
  {
    name: 'update_product_category',
    description: 'Update a product category.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Category ID to update' },
        name: { type: 'string', description: 'New category name' },
        parent_id: { type: 'number', description: 'New parent category ID' },
        description: { type: 'string', description: 'New description' },
        sequence: { type: 'number', description: 'New display sequence' },
      },
      required: ['id'],
    },
  },
  {
    name: 'delete_product_category',
    description: 'Delete a product category (will fail if category has products or children).',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Category ID to delete' },
      },
      required: ['id'],
    },
  },
];

export async function handleProductCategoryTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'list_product_categories':
      return client.listProductCategories(args);
    case 'get_product_categories_tree':
      return client.getProductCategoriesTree();
    case 'get_product_category':
      return client.getProductCategory(args.id as number);
    case 'create_product_category':
      return client.createProductCategory(args);
    case 'update_product_category': {
      const { id, ...data } = args;
      return client.updateProductCategory(id as number, data);
    }
    case 'delete_product_category':
      return client.deleteProductCategory(args.id as number);
    default:
      throw new Error(`Unknown product category tool: ${toolName}`);
  }
}
