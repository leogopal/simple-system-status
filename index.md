---
page: introduction
title: Introduction
nav: Home
group: navigation
weight: 1
layout: default
subnav:
  - title: Audience
    tag: audience
  - title: Goal
    tag: goal
  - title: Philosophy
    tag: philosophy
  - title: Contributing
    tag: contributing
updated: 6 Oct 2014
---

<div class="docs-section">
		{% capture introduction %}{% include markdown/Introduction.md %}{% endcapture %}
		{{ introduction | markdownify }}
</div>
