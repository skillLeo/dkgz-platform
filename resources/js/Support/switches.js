/**
 * Reading a switch block from a page's content.
 *
 * A switch is a content block of type `boolean`, and it reaches the page as a
 * real boolean rather than as the "1" or "0" it is stored as — the string "0" is
 * true in JavaScript, so text would read an off switch as on.
 *
 * A block that is missing, or one nobody has set, falls back to `whenUnset`.
 * That defaults to ON, because files reach the server over FTP before the seeder
 * runs: for a minute a live page has the new template and not yet the new row,
 * and a section that vanished in that window would look like the deploy had
 * broken it. A switch that turns something ON that was not there before passes
 * `false` instead, so the same window leaves the page as it was.
 *
 * The string forms are tolerated for the same reason, so a page rendered against
 * a block still stored as text behaves rather than silently inverting.
 */
export function isOn (content, section, field, whenUnset = true) {
    const value = content?.[section]?.[field]

    if (value === undefined || value === null || value === '') return whenUnset

    return value !== false && value !== '0'
}
