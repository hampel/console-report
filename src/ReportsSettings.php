<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport;

/**
 * A settings dump: sections of label-value pairs, optionally filtered to one section.
 *
 * The content stays with the application. What a tool's settings dump should contain is
 * an editorial decision - which keys matter, what they are called, which ones are worth
 * spelling out - and a package that tried to derive it from the config files would take
 * away the property that makes the output worth having: that it is exhaustive on
 * purpose, so a setting missing from it reads as a setting the tool does not have.
 */
trait ReportsSettings
{
    use RendersDetails;

    /**
     * @param array<string, array<string, string>> $sections settings by section name
     * @param string|null $only comma separated section names, or null for all of them
     */
    protected function reportSettings(array $sections, ?string $only = null): void
    {
        $wanted = $this->wantedSections($only);

        foreach ($sections as $heading => $settings) {
            if ($wanted !== [] && ! in_array($this->sectionKeyword($heading), $wanted, true)) {
                continue;
            }

            $this->reportLine();
            $this->heading($heading);

            foreach ($settings as $label => $value) {
                $this->detail($label, $value);
            }
        }

        $this->reportLine();
    }

    /**
     * @return list<string> sections asked for, empty for all of them
     */
    protected function wantedSections(?string $only): array
    {
        if ($only === null || trim($only) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $section): string => $this->sectionKeyword($section),
            explode(',', $only)
        )));
    }

    /**
     * Section names are matched loosely, so --only="migration source" and
     * --only=migration_source both find the same section.
     */
    protected function sectionKeyword(string $value): string
    {
        return str_replace([' ', '-'], '_', mb_strtolower(trim($value)));
    }
}
