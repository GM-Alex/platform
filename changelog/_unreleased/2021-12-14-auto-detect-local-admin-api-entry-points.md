---
title: Auto-detect local AdminExtensionAPI entry points in plugins
issue: NEXT-19247
flag: FEATURE_NEXT_17950
---
# Core
* Changed `\Shopware\Core\Framework\Api\Controller\InfoController::getBundles()` to auto-detect if plugins provide a local file as entry point for AdminExtensionAPI.
