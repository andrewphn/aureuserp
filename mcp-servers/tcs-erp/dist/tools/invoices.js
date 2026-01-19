/**
 * Invoice Tools - CRUD and workflow operations for customer invoices and vendor bills
 */
export const invoiceTools = [
    // Customer Invoices
    {
        name: 'list_invoices',
        description: 'List customer invoices with filters (status, partner, date range).',
        inputSchema: {
            type: 'object',
            properties: {
                status: { type: 'string', description: 'Filter by status (draft, posted, paid, cancelled)' },
                partner_id: { type: 'number', description: 'Filter by customer ID' },
                date_from: { type: 'string', description: 'Filter invoices from this date (YYYY-MM-DD)' },
                date_to: { type: 'string', description: 'Filter invoices to this date (YYYY-MM-DD)' },
                search: { type: 'string', description: 'Search by invoice number' },
                page: { type: 'number', description: 'Page number for pagination' },
                per_page: { type: 'number', description: 'Items per page (max 100)' },
            },
        },
    },
    {
        name: 'get_invoice',
        description: 'Get invoice details by ID with lines and payment info.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Invoice ID' },
                include: {
                    type: 'array',
                    items: { type: 'string' },
                    description: 'Relations to include (lines, partner, payments, salesOrder)',
                },
            },
            required: ['id'],
        },
    },
    {
        name: 'create_invoice',
        description: 'Create a new customer invoice.',
        inputSchema: {
            type: 'object',
            properties: {
                partner_id: { type: 'number', description: 'Customer ID' },
                sales_order_id: { type: 'number', description: 'Related sales order ID' },
                invoice_date: { type: 'string', description: 'Invoice date (YYYY-MM-DD)' },
                due_date: { type: 'string', description: 'Due date (YYYY-MM-DD)' },
                payment_term_id: { type: 'number', description: 'Payment terms ID' },
                reference: { type: 'string', description: 'Customer reference' },
                notes: { type: 'string', description: 'Invoice notes' },
                lines: {
                    type: 'array',
                    items: {
                        type: 'object',
                        properties: {
                            product_id: { type: 'number' },
                            name: { type: 'string' },
                            quantity: { type: 'number' },
                            price_unit: { type: 'number' },
                        },
                    },
                    description: 'Invoice line items',
                },
            },
            required: ['partner_id'],
        },
    },
    {
        name: 'update_invoice',
        description: 'Update invoice fields (only draft invoices can be updated).',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Invoice ID to update' },
                invoice_date: { type: 'string', description: 'New invoice date' },
                due_date: { type: 'string', description: 'New due date' },
                reference: { type: 'string', description: 'New reference' },
                notes: { type: 'string', description: 'New notes' },
            },
            required: ['id'],
        },
    },
    {
        name: 'delete_invoice',
        description: 'Delete an invoice (only draft invoices can be deleted).',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Invoice ID to delete' },
            },
            required: ['id'],
        },
    },
    {
        name: 'post_invoice',
        description: 'Post/validate a draft invoice, making it official.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Invoice ID to post' },
            },
            required: ['id'],
        },
    },
    {
        name: 'pay_invoice',
        description: 'Record a payment against an invoice.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Invoice ID to pay' },
                amount: { type: 'number', description: 'Payment amount' },
                payment_date: { type: 'string', description: 'Payment date (YYYY-MM-DD)' },
                payment_method: { type: 'string', description: 'Payment method (check, cash, transfer, card)' },
                reference: { type: 'string', description: 'Payment reference' },
                journal_id: { type: 'number', description: 'Payment journal ID' },
            },
            required: ['id', 'amount'],
        },
    },
    {
        name: 'create_invoice_credit_note',
        description: 'Create a credit note from an invoice.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Invoice ID to credit' },
                reason: { type: 'string', description: 'Credit note reason' },
                refund_method: { type: 'string', description: 'Refund method (refund, cancel, modify)' },
            },
            required: ['id'],
        },
    },
    {
        name: 'reset_invoice_to_draft',
        description: 'Reset a posted invoice back to draft status.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Invoice ID to reset' },
            },
            required: ['id'],
        },
    },
    {
        name: 'send_invoice_email',
        description: 'Send invoice via email to customer.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Invoice ID to send' },
                email: { type: 'string', description: 'Override recipient email' },
                subject: { type: 'string', description: 'Custom email subject' },
                message: { type: 'string', description: 'Custom email message' },
            },
            required: ['id'],
        },
    },
    // Vendor Bills
    {
        name: 'list_bills',
        description: 'List vendor bills with filters (status, vendor, date range).',
        inputSchema: {
            type: 'object',
            properties: {
                status: { type: 'string', description: 'Filter by status (draft, posted, paid, cancelled)' },
                partner_id: { type: 'number', description: 'Filter by vendor ID' },
                date_from: { type: 'string', description: 'Filter bills from this date (YYYY-MM-DD)' },
                date_to: { type: 'string', description: 'Filter bills to this date (YYYY-MM-DD)' },
                search: { type: 'string', description: 'Search by bill number or reference' },
                page: { type: 'number', description: 'Page number for pagination' },
                per_page: { type: 'number', description: 'Items per page (max 100)' },
            },
        },
    },
    {
        name: 'get_bill',
        description: 'Get vendor bill details by ID with lines and payment info.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Bill ID' },
                include: {
                    type: 'array',
                    items: { type: 'string' },
                    description: 'Relations to include (lines, partner, payments, purchaseOrder)',
                },
            },
            required: ['id'],
        },
    },
    {
        name: 'create_bill',
        description: 'Create a new vendor bill.',
        inputSchema: {
            type: 'object',
            properties: {
                partner_id: { type: 'number', description: 'Vendor ID' },
                purchase_order_id: { type: 'number', description: 'Related purchase order ID' },
                bill_date: { type: 'string', description: 'Bill date (YYYY-MM-DD)' },
                due_date: { type: 'string', description: 'Due date (YYYY-MM-DD)' },
                reference: { type: 'string', description: 'Vendor bill reference/number' },
                notes: { type: 'string', description: 'Bill notes' },
                lines: {
                    type: 'array',
                    items: {
                        type: 'object',
                        properties: {
                            product_id: { type: 'number' },
                            name: { type: 'string' },
                            quantity: { type: 'number' },
                            price_unit: { type: 'number' },
                        },
                    },
                    description: 'Bill line items',
                },
            },
            required: ['partner_id'],
        },
    },
    {
        name: 'update_bill',
        description: 'Update vendor bill fields (only draft bills can be updated).',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Bill ID to update' },
                bill_date: { type: 'string', description: 'New bill date' },
                due_date: { type: 'string', description: 'New due date' },
                reference: { type: 'string', description: 'New reference' },
                notes: { type: 'string', description: 'New notes' },
            },
            required: ['id'],
        },
    },
    {
        name: 'delete_bill',
        description: 'Delete a vendor bill (only draft bills can be deleted).',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Bill ID to delete' },
            },
            required: ['id'],
        },
    },
    {
        name: 'post_bill',
        description: 'Post/validate a draft vendor bill.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Bill ID to post' },
            },
            required: ['id'],
        },
    },
    {
        name: 'pay_bill',
        description: 'Record a payment for a vendor bill.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Bill ID to pay' },
                amount: { type: 'number', description: 'Payment amount' },
                payment_date: { type: 'string', description: 'Payment date (YYYY-MM-DD)' },
                payment_method: { type: 'string', description: 'Payment method (check, cash, transfer, card)' },
                reference: { type: 'string', description: 'Payment reference' },
                journal_id: { type: 'number', description: 'Payment journal ID' },
            },
            required: ['id', 'amount'],
        },
    },
    {
        name: 'reset_bill_to_draft',
        description: 'Reset a posted vendor bill back to draft status.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Bill ID to reset' },
            },
            required: ['id'],
        },
    },
];
export async function handleInvoiceTool(client, toolName, args) {
    switch (toolName) {
        // Invoices
        case 'list_invoices':
            return client.listInvoices(args);
        case 'get_invoice':
            return client.getInvoice(args.id, args.include);
        case 'create_invoice':
            return client.createInvoice(args);
        case 'update_invoice': {
            const { id, ...data } = args;
            return client.updateInvoice(id, data);
        }
        case 'delete_invoice':
            return client.deleteInvoice(args.id);
        case 'post_invoice':
            return client.postInvoice(args.id);
        case 'pay_invoice':
            return client.payInvoice(args.id, args);
        case 'create_invoice_credit_note':
            return client.createInvoiceCreditNote(args.id, args);
        case 'reset_invoice_to_draft':
            return client.resetInvoiceToDraft(args.id);
        case 'send_invoice_email':
            return client.sendInvoiceEmail(args.id, args);
        // Bills
        case 'list_bills':
            return client.listBills(args);
        case 'get_bill':
            return client.getBill(args.id, args.include);
        case 'create_bill':
            return client.createBill(args);
        case 'update_bill': {
            const { id, ...data } = args;
            return client.updateBill(id, data);
        }
        case 'delete_bill':
            return client.deleteBill(args.id);
        case 'post_bill':
            return client.postBill(args.id);
        case 'pay_bill':
            return client.payBill(args.id, args);
        case 'reset_bill_to_draft':
            return client.resetBillToDraft(args.id);
        default:
            throw new Error(`Unknown invoice tool: ${toolName}`);
    }
}
