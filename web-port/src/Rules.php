<?php
declare(strict_types=1);
namespace AceTR;

/** Table-driven subset of CAtumSJ. Does not claim to port full combat. */
final class Rules
{
    private array $data;
    public function __construct(?string $path = null)
    {
        $this->data = json_decode(file_get_contents($path ?? __DIR__.'/../data/rules.json'), true, 512, JSON_THROW_ON_ERROR);
    }
    public function data(): array { return $this->data; }
    public function cap(int $level): int { return $level <= 100 ? 300 : 340; }
    public function calculate(string $gear, int $level, array $stats): array
    {
        if (!isset($this->data['gears'][$gear]) || $level < 1 || $level > 110 || count($stats) !== 6) {
            throw new \InvalidArgumentException('Invalid gear, level or six-stat vector.');
        }
        foreach ($stats as $value) {
            if (!is_int($value) || $value < 0 || $value > 65535) throw new \InvalidArgumentException('Invalid stat.');
        }
        $c = $this->cap($level);
        $row = fn(int $v): array => $this->data['stats'][min($c, $v)];
        return [
            'hp' => 600, // GetCharacterHP; legacyHp table is deliberately not used.
            'shield' => (int)$row($stats[4])[5],
            'sp' => min($c, $stats[3]) * 8,
            'fuel' => min($c, $stats[2]) * 10,
            'attackRate' => $row($stats[0])[1],
            'hitBonus' => $row($stats[0])[2],
            // C++ LEVEL_STAT_VALUE declares Pierce as INT (decimal initializer truncates).
            'pierce' => (int)$row($stats[0])[3],
            'defenseRate' => $row($stats[1])[4],
            'dodgeRate' => $row($stats[5])[6],
            'cap' => $c,
        ];
    }
    public function weaponDamage(float $ability, float $modifier, int $attackStat, int $level): float
    {
        if ($ability < 0 || $attackStat < 0 || $level < 1 || $level > 110 || $modifier < -1) throw new \InvalidArgumentException('Invalid weapon inputs.');
        return $ability * (1 + $modifier) * (1 + $this->data['stats'][min($this->cap($level), $attackStat)][1]);
    }
    public function initialExperience(int $level): float
    {
        return $this->data['experience'][max(1,min(110,$level))-1][2];
    }
    public function allocate(string $gear, int $level, array $stats, int $available, int $part): array
    {
        $this->calculate($gear,$level,$stats);
        if ($part < 0 || $part > 5 || $available < 1 || $stats[$part] >= $this->cap($level)) throw new \InvalidArgumentException('Allocation unavailable.');
        // Mirrors ChangeStat: check current value before adding the class increment.
        $stats[$part] += $this->data['gears'][$gear][$part];
        return ['stats'=>$stats,'available'=>$available-1];
    }
}
