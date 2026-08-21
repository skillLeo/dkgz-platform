/**
 * The shared geometry behind every coverage map.
 *
 * Both the public map and the partner's own service-area map draw the same
 * country from the same numbers: one silhouette, one set of zone positions.
 * Keeping them here means the two maps can never drift into disagreeing about
 * where Germany is, which is what happens when each screen traces its own.
 */

/** A simplified outline of Germany, traced clockwise from the Danish border. */
export const OUTLINE = `
  M46,3 L52,2 L55,8 L60,7 L63,11 L70,9 L74,13 L72,18 L78,20 L82,26
  L80,32 L85,36 L84,42 L88,47 L86,53 L90,58 L88,64 L83,68 L85,74
  L80,79 L83,86 L79,92 L73,95 L68,101 L64,108 L57,112 L50,110
  L44,113 L38,110 L34,112 L30,106 L26,99 L22,95 L25,88 L20,84
  L16,78 L12,72 L9,65 L6,58 L10,52 L8,45 L12,39 L17,34 L15,27
  L20,21 L27,17 L33,13 L39,11 L43,6 Z
`.trim().replace(/\s+/g, ' ')

/** The viewBox the outline is drawn in. */
export const VIEW_BOX = '0 0 100 120'

/**
 * Approximate centre of each postal zone in that coordinate space, with the
 * city most people picture when they hear the digit.
 */
export const ZONES = {
    0: { x: 76, y: 63, label: 'Dresden, Leipzig, Chemnitz' },
    1: { x: 75, y: 41, label: 'Berlin, Potsdam' },
    2: { x: 41, y: 18, label: 'Hamburg, Bremen, Kiel' },
    3: { x: 41, y: 44, label: 'Hannover, Kassel, Bielefeld' },
    4: { x: 19, y: 53, label: 'Düsseldorf, Dortmund, Essen' },
    5: { x: 16, y: 66, label: 'Köln, Bonn, Aachen' },
    6: { x: 31, y: 77, label: 'Frankfurt, Wiesbaden, Saarbrücken' },
    7: { x: 33, y: 97, label: 'Stuttgart, Karlsruhe, Freiburg' },
    8: { x: 57, y: 101, label: 'München, Augsburg' },
    9: { x: 52, y: 82, label: 'Nürnberg, Würzburg, Regensburg' },
}

/**
 * How much of each postal zone the given ranges cover.
 *
 * A zone is one leading digit — 40000–49999 is zone 4 — so the share is simply
 * how many of those hundred thousand codes fall inside the partner's ranges.
 * Ranges are clamped and overlaps are merged first, because a partner who
 * entered 40000–42999 twice covers that ground once, not twice.
 *
 * @param {Array<{postal_code_from: string, postal_code_to: string}>} areas
 * @returns {Array<{digit: number, share: number, tone: string, label: string, x: number, y: number}>}
 */
export function zoneCoverage(areas = []) {
    const ranges = []

    for (const area of areas) {
        const from = Number(area.postal_code_from)
        const to = Number(area.postal_code_to)

        if (! Number.isFinite(from) || ! Number.isFinite(to)) continue

        // A reversed range is a typo, not an empty area; read it the way it
        // was meant rather than silently dropping the partner's coverage.
        ranges.push([Math.min(from, to), Math.max(from, to)])
    }

    const merged = mergeRanges(ranges)

    return Object.entries(ZONES).map(([key, zone]) => {
        const digit = Number(key)
        const low = digit * 10_000
        const high = low + 9_999

        let covered = 0

        for (const [from, to] of merged) {
            const overlap = Math.min(high, to) - Math.max(low, from) + 1

            if (overlap > 0) covered += overlap
        }

        const share = Math.min(1, covered / 10_000)

        return {
            digit,
            share,
            // Below one percent is a handful of streets, not a region; calling
            // that "covered" on a national map would overstate the network.
            tone: share >= 0.995 ? 'full' : share >= 0.01 ? 'partial' : 'none',
            ...zone,
        }
    })
}

/** @param {Array<[number, number]>} ranges */
function mergeRanges(ranges) {
    const sorted = [...ranges].sort((a, b) => a[0] - b[0])
    const merged = []

    for (const [from, to] of sorted) {
        const last = merged[merged.length - 1]

        if (last && from <= last[1] + 1) {
            last[1] = Math.max(last[1], to)

            continue
        }

        merged.push([from, to])
    }

    return merged
}
