<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport;

/**
 * Formatting for the kinds of value a settings dump reports on.
 *
 * Each one encodes a decision about how to be honest with the reader: an ambiguous path
 * says what makes it ambiguous, a setting that is missing says so rather than printing
 * as a blank, and a credential never prints at all.
 */
trait FormatsValues
{
    /**
     * A filesystem path, stated so that it can be acted on without guessing.
     *
     * Absolute paths print as they are, and so do stream wrapper URIs - a file inside a
     * phar is `phar:///opt/bin/tool`, which has nothing to be relative to. A relative path
     * is reported along with what it resolves against, because it resolves against the
     * process working directory - which under cron is wherever the crontab last changed
     * to - so the same setting names different files depending on where the command was
     * run from. Printing it bare hides exactly the thing that makes it ambiguous.
     */
    protected function path(?string $value): string
    {
        if ($value === null || $value === '') {
            return $this->notSet();
        }

        if ($this->isAbsolutePath($value)) {
            return $value;
        }

        $cwd = getcwd();

        return $cwd === false
            ? $value . ' <fg=yellow>(relative)</>'
            : $value . ' <fg=yellow>(relative to ' . $cwd . ')</>';
    }

    /**
     * A setting a working installation needs, so an empty one is worth flagging.
     */
    protected function required(?string $value): string
    {
        return $value === null || $value === '' ? $this->notSet() : $value;
    }

    /**
     * A setting that is empty in the ordinary case, so an empty one is not a fault.
     */
    protected function optional(?string $value): string
    {
        return $value === null || $value === '' ? '<fg=gray>none</>' : $value;
    }

    /**
     * A credential, reported as present or absent and never printed.
     *
     * A settings dump is the kind of output that gets pasted into a message or a ticket,
     * and a working token pasted anywhere is a working token. The length is enough to
     * tell an empty setting from a truncated paste, which is the mistake this has to
     * help diagnose.
     */
    protected function secretStatus(?string $value): string
    {
        return $value === null || $value === ''
            ? $this->notSet()
            : '<fg=green>set</> (' . mb_strlen($value) . ' characters)';
    }

    /**
     * Free-form options as written, less anything that looks like a password.
     *
     * Options like these are passed through to a binary, so an installation can put a
     * credential in one. This covers the flag spellings people actually use rather than
     * every conceivable one - credentials belong in a defaults file that the tool reads
     * for itself, and this is a backstop for when they are not.
     */
    protected function redacted(?string $value): string
    {
        $value = (string) preg_replace(
            ['/(--[\w-]*(?:pass|secret|token)[\w-]*[= ])\S+/i', '/(^|\s)(-p)\S+/'],
            ['${1}<fg=yellow>redacted</>', '${1}${2}<fg=yellow>redacted</>'],
            (string) $value
        );

        return $this->optional($value);
    }

    protected function notSet(): string
    {
        return '<fg=yellow>not set</>';
    }

    protected function isAbsolutePath(string $value): bool
    {
        return str_starts_with($value, '/')
            || str_starts_with($value, '\\\\')
            || preg_match('#^[A-Za-z]:[\\\\/]#', $value) === 1
            // A stream wrapper URI - phar://, file://, s3:// - locates a resource outright.
            // There is no working directory for it to resolve against, so saying what it is
            // relative to would be inventing an answer. The '//' is required: an rclone
            // remote is spelled 'export:backups', which has a colon and is not a path at all.
            || preg_match('#^[A-Za-z][A-Za-z0-9+.-]*://#', $value) === 1;
    }
}
