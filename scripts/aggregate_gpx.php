<?php

$gpxBaseDir = __DIR__ . '/../static/gpx';
define('AGGREGATE_FILE', '_aggregated.gpx');
define('GPX_NS', 'http://www.topografix.com/GPX/1/1');

// we compress the trkpoints and keep every Nth node
define('RETAIN_EVERY_NTH', 100);

/** @var array<string,list<string>> */
$gpxFiles = [];

foreach (glob($gpxBaseDir . '/*') as $path) {
    if (!is_dir($path)) {
        continue;
    }

    $tag = basename($path);
    $gpxFiles[$tag] = [];

    foreach (glob($gpxBaseDir . '/' . $tag . '/*') as $gpx) {
        if (basename($gpx) === AGGREGATE_FILE) {
            continue;
        }

        $dom = new DOMDocument('1.0');
        $dom->load($gpx);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('gpx', GPX_NS);
        $dom->firstElementChild->setAttribute('path', '/gpx/' . $tag . '/' . basename($gpx));
        $gpxFiles[$tag][] = $xpath;
    }
}

foreach ($gpxFiles as $tag => &$gpxes) {
    usort($gpxes, function (DOMXPath $gpx1, DOMXPath $gpx2) {
        return $gpx1->evaluate('string(/gpx:gpx/gpx:metadata/gpx:time)') <=> $gpx2->evaluate('string(/gpx:gpx/gpx:metadata/gpx:time)');
    });
}

/** @var DOMXPath[] $xpaths */
foreach ($gpxFiles as $tag => $xpaths) {
    $filename = $gpxBaseDir . '/' . $tag . '/_aggregated.gpx';
    if (file_exists($filename)) {
        unlink($filename);
    }

    $dom = new DOMDocument('1.0');
    $gpxNode = $dom->createElementNS(GPX_NS, 'gpx');
    $gpxNode->setAttributeNS(GPX_NS, 'creator', 'www.dantleech.com');
    $gpxNode->setAttributeNS(GPX_NS, 'version', '1.1');
    $dom->appendChild($gpxNode);


    foreach ($xpaths as $xpath) {
        $trkPtOffset = 0;
        $name = $xpath->evaluate('string(/gpx:gpx/gpx:metadata/gpx:name)');
        $trkNode = $dom->createElementNS(GPX_NS, 'trk');
        $trkPtList = $xpath->query('//gpx:trkpt');

        $firstPoint = null;
        foreach ($trkPtList as $trackPoint) {
            if (null === $firstPoint) {
                $firstPoint = $trackPoint;
            }
            $trkPointNode = $dom->importNode($trackPoint, true);
            if (0 === ++$trkPtOffset % RETAIN_EVERY_NTH) {
                $trkNode->appendChild($trkPointNode);
            }
        }
        $gpxNode->appendChild($trkNode);

        $waypointStartNode = $dom->createElementNS(GPX_NS, 'wpt');
        if ($firstPoint && $trackPoint) {
            $waypointStartNode->setAttribute('lat', $firstPoint->getAttribute('lat'));
            $waypointStartNode->setAttribute('lon', $firstPoint->getAttribute('lon'));
            $nameNode = $dom->createElementNS(GPX_NS, 'name', $xpath->document->firstElementChild->getAttribute('path'));
            $waypointStartNode->appendChild($nameNode);
            $gpxNode->appendChild($waypointStartNode);
        }
    }

    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->save($filename);
}
