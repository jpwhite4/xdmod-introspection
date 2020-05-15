<?php
/* ==========================================================================================
 * XDMoDLogIngestor. This ingestor parses xdmod logs to get timing information.
 */
namespace ETL\Ingestor;

use ETL\aOptions;
use ETL\iAction;
use ETL\aAction;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;

use Log;

class XDMoDLogIngestor extends pdoIngestor implements iAction
{
    /**
     * @see ETL\Ingestor\pdoIngestor::transform()
     */
    protected function transform(array $srcRecord, $orderId)
    {
        $transformedRecord = array();

        $parsed = json_decode($srcRecord['message'], true);
        if (isset($parsed['action']) && isset($parsed['start_time']) && isset($parsed['end_time'])) {
            $spacechar = strpos($parsed['action'], ' ');
            if ($spacechar > 0) {
                $transformedRecord[] = array(
                    'datasource' => $srcRecord['datasource'],
                    'sequence' => $srcRecord['sequence'],
                    'log_time' => $srcRecord['log_time'],
                    'start_time_ts' => round($parsed['start_time']),
                    'end_time_ts' => round($parsed['end_time']),
                    'etl_action' => substr($parsed['action'], 0, $spacechar),
                    'duration' => round($parsed['end_time'] - $parsed['start_time'])
                );
            }
        }
        return $transformedRecord;
    }
}
