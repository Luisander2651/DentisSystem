<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Spec 026: turns the opening hours of config/site.php (ISO day => [opens, closes]) into the lines
 * the public site shows, grouping consecutive days with the same hours: "Lunes a viernes:
 * 9:00 a. m. – 7:00 p. m.", "Sábado: …", "Domingo: Cerrado".
 */
final readonly class PublicSiteSchedule
{
    private const DAY_NAMES = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    /**
     * @param  array<int, array{0: string, 1: string}>  $days
     */
    public function __construct(private array $days) {}

    public static function fromConfig(): self
    {
        return new self(config('site.contact.schedule', []));
    }

    /**
     * @return list<array{days: string, hours: string}>
     */
    public function lines(): array
    {
        $lines = [];
        $groupStart = null;
        $groupHours = null;

        foreach (range(1, 8) as $day) {
            $hours = $day <= 7 ? $this->hoursOf($day) : '__end__';

            if ($hours === $groupHours) {
                continue;
            }

            if ($groupStart !== null) {
                $lines[] = ['days' => $this->dayRange($groupStart, $day - 1), 'hours' => $groupHours];
            }

            $groupStart = $day;
            $groupHours = $hours;
        }

        return $lines;
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public function days(): array
    {
        return $this->days;
    }

    private function hoursOf(int $day): string
    {
        if (! isset($this->days[$day])) {
            return 'Cerrado';
        }

        [$opens, $closes] = $this->days[$day];

        return $this->clock($opens).' – '.$this->clock($closes);
    }

    private function dayRange(int $first, int $last): string
    {
        if ($first === $last) {
            return self::DAY_NAMES[$first];
        }

        $joiner = $last - $first === 1 ? ' y ' : ' a ';

        return self::DAY_NAMES[$first].$joiner.mb_strtolower(self::DAY_NAMES[$last]);
    }

    private function clock(string $time): string
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));
        $suffix = $hour < 12 ? 'a. m.' : 'p. m.';
        $twelve = $hour % 12 === 0 ? 12 : $hour % 12;

        return sprintf('%d:%02d %s', $twelve, $minute, $suffix);
    }
}
