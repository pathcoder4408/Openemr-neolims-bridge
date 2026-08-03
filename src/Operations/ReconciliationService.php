<?php

namespace OpenEMR\Modules\NeoLimsBridge\Operations;

use OpenEMR\Modules\NeoLimsBridge\Repository\OperationsRepository;

final class ReconciliationService
{
    public function run(?string $connectionKey = null): array
    {
        $checks = [
            'patient' => [
                'link' => 'neolims_bridge_patient_link',
                'native' => 'patient_data',
                'link_id' => 'openemr_pid',
                'native_id' => 'pid',
            ],
            'encounter' => [
                'link' => 'neolims_bridge_encounter_link',
                'native' => 'form_encounter',
                'link_id' => 'openemr_encounter_id',
                'native_id' => 'encounter',
            ],
            'order' => [
                'link' => 'neolims_bridge_order_link',
                'native' => 'procedure_order',
                'link_id' => 'openemr_order_id',
                'native_id' => 'procedure_order_id',
            ],
            'result' => [
                'link' => 'neolims_bridge_result_link',
                'native' => 'procedure_report',
                'link_id' => 'openemr_report_id',
                'native_id' => 'procedure_report_id',
            ],
            'document' => [
                'link' => 'neolims_bridge_document_link',
                'native' => 'documents',
                'link_id' => 'openemr_document_id',
                'native_id' => 'id',
            ],
            'billing' => [
                'link' => 'neolims_bridge_billing_link',
                'native' => 'billing',
                'link_id' => 'openemr_billing_id',
                'native_id' => 'id',
            ],
        ];

        $summary = [
            'connection_key' => $connectionKey ?? '',
            'scope' => 'all',
            'checked_count' => 0,
            'ok_count' => 0,
            'mismatch_count' => 0,
            'missing_count' => 0,
            'details' => [],
        ];

        foreach ($checks as $name => $c) {
            $params = [];
            $where = '';
            if ($connectionKey !== null && $connectionKey !== '') {
                $where = ' WHERE l.connection_key=?';
                $params[] = $connectionKey;
            }
            $stmt = sqlStatement(
                "SELECT l.*, n.{$c['native_id']} native_exists
                   FROM {$c['link']} l
              LEFT JOIN {$c['native']} n
                     ON n.{$c['native_id']}=l.{$c['link_id']}
                {$where}",
                $params
            );
            while ($row = sqlFetchArray($stmt)) {
                $summary['checked_count']++;
                if (empty($row['native_exists'])) {
                    $summary['missing_count']++;
                    $summary['details'][] = [
                        'type' => $name,
                        'status' => 'missing_native',
                        'connection_key' => $row['connection_key'] ?? null,
                        'link_id' => $row[$c['link_id']] ?? null,
                    ];
                } else {
                    $summary['ok_count']++;
                }
            }
        }

        $repo = new OperationsRepository();
        $summary['run_id'] = $repo->recordReconciliation($summary);
        $summary['generated_at_utc'] = gmdate('c');
        return $summary;
    }
}
