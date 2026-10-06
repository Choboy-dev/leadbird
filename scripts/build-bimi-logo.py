#!/usr/bin/env python3
"""Trace the Lead Bird mark into an SVG Tiny P/S file for BIMI.

BIMI rejects raster data, gradients and external references, so the bird PNG is
posterised into a handful of flat colours and each colour region is traced into
a polygon. Regions are emitted largest first, which lets later regions paint
over their containers instead of needing explicit holes.

Usage: python3 scripts/build-bimi-logo.py [source.png]
Writes assets/images/leadbird-bimi.svg and a preview PNG next to it.
"""

from __future__ import annotations

import sys
from collections import deque
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter

ROOT = Path(__file__).resolve().parent.parent
SOURCE = Path(sys.argv[1]) if len(sys.argv) > 1 else ROOT / "assets/images/leadbird-favicon.png"
TARGET = ROOT / "assets/images/leadbird-bimi.svg"
PREVIEW = ROOT / "assets/images/leadbird-bimi-preview.png"

CANVAS = 512
BACKGROUND = (255, 255, 255)
COLORS = 6
MIN_AREA = 60
EPSILON = 1.1

# Moore-neighbour order, clockwise from due east.
NEIGHBOURS = [(1, 0), (1, 1), (0, 1), (-1, 1), (-1, 0), (-1, -1), (0, -1), (1, -1)]


def posterise(path: Path) -> tuple[list[list[int]], list[tuple[int, int, int]]]:
    """Flatten onto the background, smooth speckles, reduce to flat colours."""
    image = Image.open(path).convert("RGBA").resize((CANVAS, CANVAS), Image.LANCZOS)
    flat = Image.new("RGB", image.size, BACKGROUND)
    flat.paste(image, mask=image.split()[3])
    flat = flat.filter(ImageFilter.ModeFilter(3))
    quantised = flat.quantize(colors=COLORS, dither=Image.Dither.NONE)
    palette = quantised.getpalette() or []
    colours = [tuple(palette[i * 3:i * 3 + 3]) for i in range(COLORS)]
    pixels = list(quantised.getdata())
    grid = [pixels[row * CANVAS:(row + 1) * CANVAS] for row in range(CANVAS)]

    return grid, colours


def components(grid: list[list[int]]) -> list[tuple[int, list[tuple[int, int]]]]:
    """Connected same-colour regions as (colour index, pixel list), largest first."""
    seen = [[False] * CANVAS for _ in range(CANVAS)]
    found: list[tuple[int, list[tuple[int, int]]]] = []

    for y in range(CANVAS):
        for x in range(CANVAS):
            if seen[y][x]:
                continue
            index = grid[y][x]
            queue = deque([(x, y)])
            seen[y][x] = True
            pixels: list[tuple[int, int]] = []
            while queue:
                cx, cy = queue.popleft()
                pixels.append((cx, cy))
                for dx, dy in ((1, 0), (-1, 0), (0, 1), (0, -1)):
                    nx, ny = cx + dx, cy + dy
                    if 0 <= nx < CANVAS and 0 <= ny < CANVAS and not seen[ny][nx] and grid[ny][nx] == index:
                        seen[ny][nx] = True
                        queue.append((nx, ny))
            if len(pixels) >= MIN_AREA:
                found.append((index, pixels))

    found.sort(key=lambda item: len(item[1]), reverse=True)
    return found


def outline(member: set[tuple[int, int]]) -> list[tuple[int, int]]:
    """Moore-neighbour trace of a pixel set's outer boundary."""
    start = min(member, key=lambda p: (p[1], p[0]))
    contour = [start]
    current = start
    backtrack = (start[0] - 1, start[1])

    while True:
        offset = NEIGHBOURS.index((backtrack[0] - current[0], backtrack[1] - current[1]))
        moved = False
        for step in range(1, 9):
            dx, dy = NEIGHBOURS[(offset + step) % 8]
            candidate = (current[0] + dx, current[1] + dy)
            if candidate in member:
                backtrack = (current[0] + NEIGHBOURS[(offset + step - 1) % 8][0],
                             current[1] + NEIGHBOURS[(offset + step - 1) % 8][1])
                current = candidate
                moved = True
                break
        if not moved or (current == start and len(contour) > 2):
            break
        contour.append(current)
        if len(contour) > 40000:
            break

    return contour


def holes(member: set[tuple[int, int]]) -> list[set[tuple[int, int]]]:
    """Enclosed gaps in a region, so ring shapes do not fill solid."""
    xs = [x for x, _ in member]
    ys = [y for _, y in member]
    x0, x1, y0, y1 = min(xs) - 1, max(xs) + 1, min(ys) - 1, max(ys) + 1

    outside: set[tuple[int, int]] = set()
    queue: deque[tuple[int, int]] = deque()
    border = [(x, y) for x in (x0, x1) for y in range(y0, y1 + 1)]
    border += [(x, y) for y in (y0, y1) for x in range(x0, x1 + 1)]
    for point in border:
        if point not in member and point not in outside:
            outside.add(point)
            queue.append(point)
    while queue:
        cx, cy = queue.popleft()
        for dx, dy in ((1, 0), (-1, 0), (0, 1), (0, -1)):
            point = (cx + dx, cy + dy)
            if x0 <= point[0] <= x1 and y0 <= point[1] <= y1 and point not in member and point not in outside:
                outside.add(point)
                queue.append(point)

    enclosed: list[set[tuple[int, int]]] = []
    seen: set[tuple[int, int]] = set()
    for y in range(y0, y1 + 1):
        for x in range(x0, x1 + 1):
            start = (x, y)
            if start in member or start in outside or start in seen:
                continue
            gap: set[tuple[int, int]] = {start}
            seen.add(start)
            queue = deque([start])
            while queue:
                cx, cy = queue.popleft()
                for dx, dy in ((1, 0), (-1, 0), (0, 1), (0, -1)):
                    point = (cx + dx, cy + dy)
                    if x0 <= point[0] <= x1 and y0 <= point[1] <= y1 and point not in member and point not in seen:
                        seen.add(point)
                        gap.add(point)
                        queue.append(point)
            if len(gap) >= 12:
                enclosed.append(gap)

    return enclosed


def orient(ring: list[tuple[int, int]], clockwise: bool) -> list[tuple[int, int]]:
    """Holes are wound against their outer ring so plain nonzero fill knocks them out."""
    area = sum(x1 * y2 - x2 * y1 for (x1, y1), (x2, y2) in zip(ring, ring[1:] + ring[:1]))
    return ring if (area < 0) == clockwise else ring[::-1]


def simplify(points: list[tuple[int, int]], epsilon: float) -> list[tuple[int, int]]:
    """Ramer-Douglas-Peucker, iterative so long contours cannot blow the stack."""
    if len(points) < 3:
        return points
    keep = [False] * len(points)
    keep[0] = keep[-1] = True
    stack = [(0, len(points) - 1)]

    while stack:
        first, last = stack.pop()
        if last <= first + 1:
            continue
        ax, ay = points[first]
        bx, by = points[last]
        dx, dy = bx - ax, by - ay
        length = (dx * dx + dy * dy) ** 0.5
        worst, index = 0.0, first
        for i in range(first + 1, last):
            px, py = points[i]
            distance = (abs(dx * (ay - py) - dy * (ax - px)) / length) if length else (
                ((px - ax) ** 2 + (py - ay) ** 2) ** 0.5)
            if distance > worst:
                worst, index = distance, i
        if worst > epsilon:
            keep[index] = True
            stack.append((first, index))
            stack.append((index, last))

    return [point for point, wanted in zip(points, keep) if wanted]


def build() -> None:
    grid, colours = posterise(SOURCE)
    regions = components(grid)

    shapes: list[tuple[tuple[int, int, int], list[list[tuple[int, int]]]]] = []
    for index, pixels in regions:
        member = set(pixels)
        rings = [simplify(outline(member), EPSILON)]
        rings += [simplify(outline(gap), EPSILON) for gap in holes(member)]
        rings = [ring for ring in rings if len(ring) >= 3]
        if rings:
            wound = [orient(rings[0], True)] + [orient(ring, False) for ring in rings[1:]]
            shapes.append((colours[index], wound))

    paths = []
    for colour, rings in shapes:
        subpaths = "".join("M" + " ".join(f"{x} {y}" for x, y in ring) + "Z" for ring in rings)
        paths.append(f'<path fill="#{"%02x%02x%02x" % colour}" d="{subpaths}"/>')

    svg = (
        '<svg xmlns="http://www.w3.org/2000/svg" version="1.2" baseProfile="tiny-ps" '
        f'viewBox="0 0 {CANVAS} {CANVAS}" width="{CANVAS}" height="{CANVAS}">'
        "<title>Lead Bird</title>"
        f'<rect fill="#{"%02x%02x%02x" % BACKGROUND}" width="{CANVAS}" height="{CANVAS}"/>'
        + "".join(paths)
        + "</svg>\n"
    )
    TARGET.write_text(svg)

    preview = Image.new("RGB", (CANVAS, CANVAS), BACKGROUND)
    for colour, rings in shapes:
        mask = Image.new("L", (CANVAS, CANVAS), 0)
        stencil = ImageDraw.Draw(mask)
        stencil.polygon(rings[0], fill=255)
        for ring in rings[1:]:
            stencil.polygon(ring, fill=0)
        preview.paste(colour, mask=mask)
    preview.save(PREVIEW)

    print(f"{TARGET.relative_to(ROOT)}: {TARGET.stat().st_size} bytes, {len(paths)} paths")
    print("colours: " + ", ".join("#%02x%02x%02x" % c for c in colours))


if __name__ == "__main__":
    build()
