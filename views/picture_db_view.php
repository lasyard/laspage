<?php
$count = count($files);
$from = $page * $picsPerPage;
$to = min(($page + 1) * $picsPerPage, $count);
$totalPages = ($count - 1) / $picsPerPage;

$navArgs = array(
    'count' => $count,
    'page' => $page,
    'totalPages' => $totalPages,
    'from' => $from,
    'to' => $to,
    'url' => function ($pg) use ($baseUrl) {
        return $baseUrl . $pg;
    },
);

Sys::render('picture_nav_view', $navArgs);

$app = Sys::app();
for ($i = $from; $i < $to; $i++) {
    $file = $files[$i];
    $args = array(
        'src' => $app->fileUrl($file),
    );
    $fileName = pathinfo($file, PATHINFO_FILENAME);
    if ($app->canEdit) {
        $args['fileName'] = $fileName;
    }
    $date = substr($fileName, 0, 6);
    if (isset($titles) && array_key_exists($fileName, $titles)) {
        $title = $titles[$fileName];
    } else {
        $title = '';
    }
    $args['title'] = $date . ' ' . $title;
    Sys::render('picture_view', $args);
}

Sys::render('picture_nav_view', $navArgs);
