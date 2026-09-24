<?php
$case = $GLOBALS['case'];
if (http_response_code() !== 200 || !in_array($case, ['parts-partial', 'parts-brand', 'taxonomy-empty-base', 'landing-empty-base', 'landing-partial'], true)) {
    throw new RuntimeException("Unexpected catalog response: {$case}");
}
if (str_contains($case, 'empty')) {
    if ($page !== 1 || $robotsMeta !== 'noindex, follow') {
        throw new RuntimeException("Empty first page must be noindex: {$case}");
    }
} elseif ($case === 'parts-brand') {
    if ($robotsMeta !== 'index, follow' || !str_contains($pageTitle, 'Toyota') || !str_contains($h1_title, 'Toyota')) {
        throw new RuntimeException('Brand URL needs an intent-specific title/H1');
    }
} elseif ($page !== 2 || $robotsMeta !== 'index, follow' || !str_contains($pageTitle, 'صفحه 2')
    || !str_contains($metaDescription, 'صفحه 2') || !str_ends_with($canonicalUrl, '?page=2')) {
    throw new RuntimeException("Valid page 2 needs a unique title and self canonical: {$case}");
}
echo "PASS: {$case} returned HTTP 200 with {$robotsMeta}\n";
