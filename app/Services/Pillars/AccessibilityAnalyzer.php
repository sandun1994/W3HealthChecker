<?php

namespace App\Services\Pillars;

use App\Services\Scanner\HtmlDocument;

class AccessibilityAnalyzer
{
    public function analyze(HtmlDocument $doc, array $fetchData): array
    {
        $issues = [];
        $penalties = 0;
        $finalUrl = $fetchData['final_url'] ?? '';

        $lang = $doc->getHtmlLang();
        $images = $doc->getImages();
        $buttons = $doc->getButtons();
        $links = $doc->getLinks();
        $formInputs = $doc->getFormInputs();
        $iframes = $doc->getIframes();
        $duplicateIds = $doc->getDuplicateIds();

        // 1. HTML lang attribute
        if (empty($lang)) {
            $penalties += 20;
            $issues[] = [
                'rule_id' => 'a11y_html_lang_missing',
                'category' => 'accessibility',
                'severity' => 'high',
                'confidence' => 'high',
                'title' => 'Missing "lang" Attribute on <html> Element',
                'affected_resource' => $finalUrl,
                'evidence' => ['tag' => '<html>', 'attribute' => 'lang', 'present' => false],
                'why_it_matters' => 'Screen readers require the document language declaration to apply correct pronunciation and text-to-speech inflection rules.',
                'recommendation' => 'Specify a valid BCP 47 language code on the root HTML element (e.g. <html lang="en">).',
                'technical_details' => '<html lang="en">',
            ];
        }

        // 2. Empty Buttons
        $emptyButtons = 0;
        foreach ($buttons as $btn) {
            $hasText = !empty($btn['text']);
            $hasAria = !empty($btn['aria_label']) || !empty($btn['aria_labelledby']);
            if (!$hasText && !$hasAria) {
                $emptyButtons++;
            }
        }
        if ($emptyButtons > 0) {
            $penalties += min(18, $emptyButtons * 5);
            $issues[] = [
                'rule_id' => 'a11y_empty_buttons',
                'category' => 'accessibility',
                'severity' => 'high',
                'confidence' => 'high',
                'title' => "{$emptyButtons} Button(s) Without Accessible Name",
                'affected_resource' => $finalUrl,
                'evidence' => ['empty_buttons_count' => $emptyButtons],
                'why_it_matters' => 'Screen reader users hear "button" without any description of what action clicking it will execute.',
                'recommendation' => 'Add descriptive text inside the button or provide an aria-label attribute.',
                'technical_details' => '<button aria-label="Open navigation menu"><svg ...></svg></button>',
            ];
        }

        // 3. Empty Links
        $emptyLinks = 0;
        foreach ($links as $link) {
            $hasText = !empty($link['text']);
            $hasAria = !empty($link['aria_label']);
            if (!$hasText && !$hasAria && !empty($link['href'])) {
                $emptyLinks++;
            }
        }
        if ($emptyLinks > 0) {
            $penalties += min(15, $emptyLinks * 4);
            $issues[] = [
                'rule_id' => 'a11y_empty_links',
                'category' => 'accessibility',
                'severity' => 'medium',
                'confidence' => 'high',
                'title' => "{$emptyLinks} Link(s) Without Discernible Anchor Text",
                'affected_resource' => $finalUrl,
                'evidence' => ['empty_links_count' => $emptyLinks],
                'why_it_matters' => 'Screen reader users navigating via link lists cannot understand where the link leads.',
                'recommendation' => 'Provide clear anchor text or an aria-label describing the destination.',
                'technical_details' => '<a href="/dashboard" aria-label="User Dashboard"><i class="icon"></i></a>',
            ];
        }

        // 4. Form Inputs Missing Labels
        $unlabeledInputs = 0;
        foreach ($formInputs as $input) {
            if (!$input['has_label']) {
                $unlabeledInputs++;
            }
        }
        if ($unlabeledInputs > 0) {
            $penalties += min(15, $unlabeledInputs * 4);
            $issues[] = [
                'rule_id' => 'a11y_form_labels_missing',
                'category' => 'accessibility',
                'severity' => 'high',
                'confidence' => 'high',
                'title' => "{$unlabeledInputs} Form Control(s) Missing Accessible Labels",
                'affected_resource' => $finalUrl,
                'evidence' => ['unlabeled_inputs_count' => $unlabeledInputs],
                'why_it_matters' => 'Form fields without associated <label> elements or aria-label attributes prevent screen reader users from understanding what data is expected.',
                'recommendation' => 'Pair every input with an explicit <label for="input-id"> or wrap the input inside a <label> tag.',
                'technical_details' => '<label for="user-email">Email Address</label>\n<input type="email" id="user-email" name="email">',
            ];
        }

        // 5. Iframes Missing Title
        $unlabeledIframes = 0;
        foreach ($iframes as $iframe) {
            if (!$iframe['has_title']) {
                $unlabeledIframes++;
            }
        }
        if ($unlabeledIframes > 0) {
            $penalties += min(10, $unlabeledIframes * 4);
            $issues[] = [
                'rule_id' => 'a11y_iframe_title_missing',
                'category' => 'accessibility',
                'severity' => 'medium',
                'confidence' => 'high',
                'title' => "{$unlabeledIframes} <iframe> Element(s) Missing Title Attribute",
                'affected_resource' => $finalUrl,
                'evidence' => ['unlabeled_iframes_count' => $unlabeledIframes],
                'why_it_matters' => 'Screen readers use iframe title attributes to announce embedded frames (e.g. video players, maps) before entering them.',
                'recommendation' => 'Add a descriptive title attribute to every <iframe> element.',
                'technical_details' => '<iframe src="..." title="Interactive Location Map"></iframe>',
            ];
        }

        // 6. Duplicate Element IDs
        if (!empty($duplicateIds)) {
            $dupCount = count($duplicateIds);
            $penalties += min(8, $dupCount * 3);
            $issues[] = [
                'rule_id' => 'a11y_duplicate_ids',
                'category' => 'accessibility',
                'severity' => 'medium',
                'confidence' => 'high',
                'title' => "Duplicate ID Attribute(s) Detected ({$dupCount} IDs duplicated)",
                'affected_resource' => $finalUrl,
                'evidence' => ['duplicate_ids' => array_slice($duplicateIds, 0, 5, true)],
                'why_it_matters' => 'IDs must be unique across the document. Duplicate IDs break ARIA referencing (aria-labelledby, aria-describedby) and label associations.',
                'recommendation' => 'Ensure every id attribute value in the HTML document is globally unique.',
                'technical_details' => 'Found duplicated IDs: ' . implode(', ', array_keys(array_slice($duplicateIds, 0, 3, true))),
            ];
        }

        $score = max(15, 100 - $penalties);

        return [
            'score' => $score,
            'issues' => $issues,
            'summary' => [
                'html_lang' => $lang,
                'buttons_count' => count($buttons),
                'empty_buttons' => $emptyButtons,
                'links_count' => count($links),
                'empty_links' => $emptyLinks,
                'form_inputs_count' => count($formInputs),
                'unlabeled_inputs' => $unlabeledInputs,
                'iframes_count' => count($iframes),
                'unlabeled_iframes' => $unlabeledIframes,
                'duplicate_ids_count' => count($duplicateIds),
                'audit_disclaimer' => 'This is a lightweight automated accessibility audit evaluating common technical barriers. It does not replace a comprehensive manual WCAG 2.2 Level AA assessment with screen-reader testing.',
            ],
        ];
    }
}
