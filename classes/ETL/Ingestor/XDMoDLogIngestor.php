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
    private function demangleTime($time)
    {
        if (is_numeric($time)) {
            return ceil($time);
        }
        else if (strpos($time, 'm') === strlen($time) - 1) {
            return ceil(substr($time, 0, strlen($time) - 1)) * 60;
        }
        print $time . "\n";
        return -1;
    }

    /**
     * @see ETL\Ingestor\pdoIngestor::transform()
     */
    protected function transform(array $srcRecord, $orderId)
    {
        $transformedRecord = array();

        $parsed = json_decode($srcRecord['message'], true);
        if (isset($parsed['action']) && isset($parsed['elapsed_time'])) {
            $spacechar = strpos($parsed['action'], ' ');
            if ($spacechar > 0) {
                $transformedRecord[] = array(
                    'sequence' => $srcRecord['sequence'],
                    'log_time' => $srcRecord['log_time'],
                    'etl_action' => substr($parsed['action'], 0, $spacechar),
                    'duration' => $this->demangleTime($parsed['elapsed_time'])
                );
            }
        }
        return $transformedRecord;
    }
}
