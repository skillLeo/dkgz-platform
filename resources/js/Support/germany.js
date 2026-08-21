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

/**
 * Germany's border as real coordinates, traced clockwise from Flensburg.
 *
 * The outline used to be hand-drawn in the SVG's own coordinate space, which
 * meant it looked approximately like Germany and the markers sat wherever they
 * had been nudged to. These are longitude/latitude pairs, so the country and
 * every point on it are projected from the same numbers and cannot disagree.
 *
 * @type {Array<[number, number]>}
 */
export const BORDER = [
    // Baltic coast, west to east
    [9.43, 54.83], [9.90, 54.78], [10.13, 54.45], [10.75, 54.31], [11.10, 54.42],
    [11.45, 54.10], [12.10, 54.18], [12.45, 54.44], [13.10, 54.32], [13.44, 54.68],
    [13.72, 54.57], [14.25, 53.93],
    // Polish border, north to south
    [14.41, 53.28], [14.14, 52.96], [14.55, 52.35], [14.72, 52.09], [14.60, 51.80],
    [14.99, 51.15],
    // Czech border
    [14.31, 51.05], [13.55, 50.73], [12.95, 50.40], [12.55, 50.39], [12.10, 50.32],
    [12.30, 50.10], [12.48, 49.94], [12.50, 49.63], [13.20, 49.12], [13.83, 48.77],
    // Austrian border, east to west
    [13.45, 48.57], [13.00, 48.27], [12.76, 48.12], [13.00, 47.85], [13.00, 47.63],
    [12.20, 47.70], [11.60, 47.58], [11.10, 47.42], [10.45, 47.55], [10.28, 47.38],
    // Switzerland
    [9.68, 47.55], [9.18, 47.66], [8.60, 47.80], [8.20, 47.62], [7.59, 47.58],
    // France, up the Rhine
    [7.55, 48.05], [7.80, 48.58], [8.23, 48.97], [7.60, 49.05], [7.05, 49.11],
    [6.95, 49.20], [6.35, 49.45],
    // Luxembourg and Belgium
    [6.13, 49.97], [6.35, 50.32], [6.02, 50.75],
    // Netherlands
    [6.05, 51.20], [6.10, 51.85], [6.72, 52.24], [7.07, 52.43], [6.70, 53.12],
    // North Sea coast, west to east
    [7.05, 53.30], [7.20, 53.37], [8.11, 53.52], [8.30, 53.72], [8.58, 53.55],
    [8.70, 53.87], [9.00, 53.90], [8.60, 54.32], [8.30, 54.90], [8.66, 54.90],
]

/** Real centres of the ten postal zones, as longitude/latitude. */
export const ZONE_COORDINATES = {
    0: [13.74, 51.05],
    1: [13.40, 52.52],
    2: [9.99, 53.55],
    3: [9.73, 52.37],
    4: [6.78, 51.23],
    5: [6.96, 50.94],
    6: [8.68, 50.11],
    7: [9.18, 48.78],
    8: [11.58, 48.14],
    9: [11.08, 49.45],
}

/** Web Mercator, which is what every map of Germany people have seen uses. */
const mercatorY = (lat) => Math.log(Math.tan(Math.PI / 4 + (lat * Math.PI / 180) / 2))

const BOUNDS = (() => {
    const xs = BORDER.map(([lon]) => lon)
    const ys = BORDER.map(([, lat]) => mercatorY(lat))

    return {
        minX: Math.min(...xs),
        maxX: Math.max(...xs),
        minY: Math.min(...ys),
        maxY: Math.max(...ys),
    }
})()

/** The drawing area the projection fills, with a little room for the markers. */
export const MAP = { width: 200, height: 260, pad: 10 }

/**
 * Longitude/latitude to a point in the map's coordinate space.
 *
 * @param {[number, number]} coordinate
 * @returns {{x: number, y: number}}
 */
export function project ([lon, lat]) {
    const { minX, maxX, minY, maxY } = BOUNDS
    const inner = { w: MAP.width - MAP.pad * 2, h: MAP.height - MAP.pad * 2 }

    return {
        x: MAP.pad + ((lon - minX) / (maxX - minX)) * inner.w,
        // Screen y grows downward; latitude grows upward.
        y: MAP.pad + ((maxY - mercatorY(lat)) / (maxY - minY)) * inner.h,
    }
}

/** The border as an SVG path, projected once. */
export const BORDER_PATH = BORDER
    .map((coordinate, index) => {
        const { x, y } = project(coordinate)

        return `${index === 0 ? 'M' : 'L'}${x.toFixed(2)},${y.toFixed(2)}`
    })
    .join(' ') + ' Z'

/** Each postal zone at its real position, ready to draw. */
export const ZONE_POINTS = Object.fromEntries(
    Object.entries(ZONE_COORDINATES).map(([digit, coordinate]) => [digit, project(coordinate)])
)
