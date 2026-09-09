{{--
    The hexagon, in one file.

    Sir Peter, 2026-09-09: "we are now and only using the hexagon style badges
    across the users." Two elements need the shape -- the badge crest and the
    client's profile crest on the dashboard -- and when they each carried their
    own copy they were both wrong in the same way and had to be found twice.

    A regular hexagon: the waist corners sit at 25% and 75%, mirrored about the
    middle, and the box is 2/sqrt(3) taller than it is wide. Pull the waist up,
    as this was at 14%/62%, and the bottom becomes a spike -- a crest, or a
    shield. Sir Peter saw that beside the hexagons he wanted on 2026-09-10 and
    said "and not these style".

    Anything that needs the shape adds .hex-shape and sets its own height to
    round(width * 1.1547). Nothing else draws the polygon.
--}}
@once
@push('styles')
<style>
    .hex-shape { clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0 75%, 0 25%); }
</style>
@endpush
@endonce
