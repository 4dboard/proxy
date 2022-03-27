<?php /* yxorP */


namespace Predis\Command;


class HashScan extends Command
{

    public function getId(): string
    {
        return 'HSCAN';
    }

    public function parseResponse($data)
    {
        if (is_array($data)) {
            $fields = $data[1];
            $result = array();

            foreach ($fields as $i => $iValue) {
                $result[$iValue] = $fields[++$i];
            }

            $data[1] = $result;
        }

        return $data;
    }

    protected function filterArguments(array $arguments): array
    {
        if (count($arguments) === 3 && is_array($arguments[2])) {
            $options = $this->prepareOptions(array_pop($arguments));
            $arguments = array_merge($arguments, $options);
        }

        return $arguments;
    }

    protected function prepareOptions($options): array
    {
        $options = array_change_key_case($options, CASE_UPPER);
        $normalized = array();

        if (!empty($options['MATCH'])) {
            $normalized[] = 'MATCH';
            $normalized[] = $options['MATCH'];
        }

        if (!empty($options['COUNT'])) {
            $normalized[] = 'COUNT';
            $normalized[] = $options['COUNT'];
        }

        return $normalized;
    }
}
