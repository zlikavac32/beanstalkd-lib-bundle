<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Console;

use Ds\Map;
use Ds\Sequence;
use Ds\Vector;
use LogicException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Client;
use function Zlikavac32\BeanstalkdLib\microTimeToHuman;

class TubeStatsTableDumper {

    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function dump(OutputInterface $output, Sequence $columnsUsed, int $sortColumn = 0, bool $descending = false): void {
        $headers = new Map();

        $headers->put(TableDumperColumn::TUBE_NAME(), 'Tube name');
        $headers->put(TableDumperColumn::WATCHING(), 'Watching');
        $headers->put(TableDumperColumn::READY(), 'Ready');
        $headers->put(TableDumperColumn::RESERVED(), 'Reserved');
        $headers->put(TableDumperColumn::DELAYED(), 'Delayed');
        $headers->put(TableDumperColumn::BURIED(), 'Buried');
        $headers->put(TableDumperColumn::PAUSED_TIME(), 'Paused for next');

        $table = new Table($output);

        $headerTitles = $columnsUsed->map(function (TableDumperColumn $column) use ($headers): string {
            return $headers->get($column);
        })->toArray();

        $headerTitles[$sortColumn] = ($descending ? '> ' : '< ') . $headerTitles[$sortColumn];

        $table->setHeaders($headerTitles);

        /** @var Vector|Vector[]|mixed[][] $data */
        $data = new Vector();

        foreach ($this->client->tubes() as $tubeName => $tube) {
            $tubeStats = $tube->stats();
            $tubeMetrics = $tubeStats->metrics();

            $data->push($columnsUsed->map(function (TableDumperColumn $column) use ($tubeName, $tubeStats, $tubeMetrics) {
                switch ($column) {
                    case TableDumperColumn::TUBE_NAME():
                        return $tubeName;
                    case TableDumperColumn::WATCHING():
                        return $tubeMetrics->numberOfClientsWatching();
                    case TableDumperColumn::READY():
                        return $tubeMetrics->numberOfReadyJobs();
                    case TableDumperColumn::RESERVED():
                        return $tubeMetrics->numberOfReservedJobs();
                    case TableDumperColumn::DELAYED():
                        return $tubeMetrics->numberOfDelayedJobs();
                    case TableDumperColumn::BURIED():
                        return $tubeMetrics->numberOfBuriedJobs();
                    case TableDumperColumn::PAUSED_TIME():
                        return $tubeStats->remainingPauseTime();
                }

                throw new LogicException(sprintf('Unknown column %s', $column));
            }));
        }

        $data = $data->sorted(function (Vector $first, Vector $second) use ($sortColumn, $descending): int {
            if ($descending) {
                return $second->get($sortColumn) <=> $first->get($sortColumn);
            }

            return $first->get($sortColumn) <=> $second->get($sortColumn);
        })->map(function (Sequence $sequence) use ($columnsUsed): Sequence {
            foreach ($columnsUsed as $k => $column) {
                $v = $sequence->get($k);

                switch ($column) {
                    case TableDumperColumn::WATCHING():
                    case TableDumperColumn::READY():
                    case TableDumperColumn::RESERVED():
                    case TableDumperColumn::DELAYED():
                    case TableDumperColumn::BURIED():
                        $v = (int) $v;
                        break;
                    case TableDumperColumn::PAUSED_TIME():
                        $v = $v > 0 ? microTimeToHuman($v) : '-/-';
                        break;
                }

                $sequence->set($k, $v);
            }

            return $sequence;
        });

        foreach ($data as $row) {
            $table->addRow($row->toArray());
        }

        $table->render();
    }
}
