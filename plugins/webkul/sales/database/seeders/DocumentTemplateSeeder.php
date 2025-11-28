<?php

namespace Webkul\Sale\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Sale\Models\DocumentTemplate;

/**
 * Document Template Seeder database seeder
 *
 */
class DocumentTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Proposal Templates - Standard
            [
                'name' => 'Standard Proposal Template',
                'type' => DocumentTemplate::TYPE_PROPOSAL,
                'template_path' => '/Users/andrewphan/tcsadmin/templates/proposals/tcs-proposal-template.html',
                'description' => 'Standard TCS proposal template with project timeline and specifications',
                'is_default' => false,
                'variables' => $this->getProposalVariables(),
            ],
            [
                'name' => 'Proposal with 30% Deposit',
                'type' => DocumentTemplate::TYPE_PROPOSAL,
                'template_path' => '/Users/andrewphan/tcsadmin/templates/proposals/tcs-proposal-30percent-template.html',
                'description' => 'Proposal template emphasizing 30% deposit requirement',
                'is_default' => false,
                'variables' => $this->getProposalVariables(),
            ],

            // Proposal Templates - Watchtower Style (Compact, Professional)
            [
                'name' => 'Watchtower Proposal Template',
                'type' => DocumentTemplate::TYPE_PROPOSAL,
                'template_path' => '/Users/andrewphan/tcsadmin/templates/proposals/watchtower-proposal-template.html',
                'description' => 'Professional compact proposal template based on Watchtower formatting',
                'is_default' => true,
                'variables' => $this->getProposalVariables(),
            ],

            // Invoice Templates - 30% Deposit
            [
                'name' => '30% Deposit Invoice (Standard)',
                'type' => DocumentTemplate::TYPE_INVOICE_DEPOSIT,
                'template_path' => '/Users/andrewphan/tcsadmin/templates/invoices/tcs-invoice-30percent-template.html',
                'description' => 'Initial 30% deposit invoice for project start',
                'is_default' => false,
                'variables' => $this->getInvoiceVariables(),
            ],
            [
                'name' => 'Watchtower 30% Deposit Invoice',
                'type' => DocumentTemplate::TYPE_INVOICE_DEPOSIT,
                'template_path' => '/Users/andrewphan/tcsadmin/templates/invoices/watchtower-invoice-30percent-template.html',
                'description' => 'Professional 30% deposit invoice based on Watchtower formatting',
                'is_default' => true,
                'variables' => $this->getInvoiceVariables(),
            ],

            // Invoice Templates - Progress
            [
                'name' => 'Standard Invoice',
                'type' => DocumentTemplate::TYPE_INVOICE_PROGRESS,
                'template_path' => '/Users/andrewphan/tcsadmin/templates/invoices/tcs-invoice-template.html',
                'description' => 'Standard invoice template for progress payments',
                'is_default' => true,
                'variables' => $this->getInvoiceVariables(),
            ],

            // Invoice Templates - Final Payment
            [
                'name' => 'Final Payment Invoice',
                'type' => DocumentTemplate::TYPE_INVOICE_FINAL,
                'template_path' => '/Users/andrewphan/tcsadmin/templates/invoices/invoice-TCS-0527-WT-FINAL.html',
                'description' => 'Final payment invoice (70% balance)',
                'is_default' => true,
                'variables' => $this->getInvoiceVariables(),
            ],
        ];

        foreach ($templates as $templateData) {
            // Only create if template file exists
            if (isset($templateData['template_path']) && file_exists($templateData['template_path'])) {
                DocumentTemplate::updateOrCreate(
                    [
                        'name' => $templateData['name'],
                        'type' => $templateData['type'],
                    ],
                    $templateData
                );
            }
        }
    }

    /**
     * Get proposal template variables
     */
    protected function getProposalVariables(): array
    {
        return [
            'PROPOSAL_NUMBER',
            'CLIENT_NAME',
            'CLIENT_DEPARTMENT',
            'CLIENT_ACCOUNT',
            'PROJECT_DATE',
            'PROJECT_TYPE',
            'ITEM_1_NAME',
            'ITEM_1_DESC',
            'ITEM_1_QTY',
            'ITEM_1_PRICE',
            'TOTAL_PRICE',
            'DEPOSIT_AMOUNT',
            'BALANCE_AMOUNT',
            'TIMELINE_DAYS',
            'WOOD_SPECIES',
            'DIMENSIONS',
            'STAIN_NAME',
            'FINISH_TYPE',
            'PROJECT_NOTES',
        ];
    }

    /**
     * Get invoice template variables
     */
    protected function getInvoiceVariables(): array
    {
        return [
            'ORDER_NUMBER',
            'CLIENT_NAME',
            'CLIENT_STREET',
            'CLIENT_CITY',
            'CLIENT_STATE',
            'CLIENT_ZIP',
            'PROJECT_DATE',
            'TOTAL_PRICE',
            'DEPOSIT_AMOUNT',
            'BALANCE_AMOUNT',
            'SUBTOTAL',
            'TAX_AMOUNT',
            'ITEM_1_NAME',
            'ITEM_1_DESC',
            'ITEM_1_QTY',
            'ITEM_1_PRICE',
        ];
    }

    /**
     * Get contract template variables
     */
    protected function getContractVariables(): array
    {
        return [
            'PROJECT_NUMBER',
            'CLIENT_NAME',
            'PROJECT_DATE',
            'TOTAL_PRICE',
            'DEPOSIT_AMOUNT',
            'PROJECT_NOTES',
            'TIMELINE_DAYS',
        ];
    }

    /**
     * Get receipt template variables
     */
    protected function getReceiptVariables(): array
    {
        return [
            'ORDER_NUMBER',
            'CLIENT_NAME',
            'PROJECT_DATE',
            'TOTAL_PRICE',
            'DEPOSIT_AMOUNT',
        ];
    }

    /**
     * Get bill of lading template variables
     */
    protected function getBolVariables(): array
    {
        return [
            'ORDER_NUMBER',
            'CLIENT_NAME',
            'CLIENT_STREET',
            'CLIENT_CITY',
            'CLIENT_STATE',
            'CLIENT_ZIP',
            'PROJECT_DATE',
            'ITEM_1_NAME',
            'ITEM_1_QTY',
        ];
    }
}
