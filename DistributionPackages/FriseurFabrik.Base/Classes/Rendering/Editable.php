<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Rendering;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\ComponentEngine\StringComponent;
use PackageFactory\Neos\ComponentEngine\NeosContext;

/**
 * Inline editable node property as a component slot.
 *
 * Unlike NeosAccessInterface::getEditable() the value is always wrapped in its own element
 * (like Neos.Neos:Editable in Fusion), so CKEditor can edit multi-paragraph texts.
 */
#[Flow\Proxy(false)]
final class Editable
{
    public static function of(NeosContext $context, string $propertyName, bool $block = false, ?Node $node = null): ComponentInterface
    {
        $node ??= $context->node;
        $value = $node->getProperty($propertyName);
        $value = is_string($value) ? $value : '';

        if (!$context->renderingMode->isEdit || !self::mayEdit($node)) {
            // resolves node:// and asset:// links; locked content (e.g. Impressum for salon accounts) gets no inline editor
            return $context->neos->convertUris($value, $context->subgraph);
        }

        $tagName = $block ? 'div' : 'span';
        return StringComponent::fromHtmlString(sprintf(
            '<%1$s data-__neos-property="%2$s" data-__neos-editable-node-contextpath="%3$s">%4$s</%1$s>',
            $tagName,
            htmlspecialchars($propertyName),
            htmlspecialchars(NodeAddress::fromNode($node)->toJson()),
            $value,
        ));
    }

    /** @var array<string,bool> */
    private static array $editPermissions = [];

    private static function mayEdit(Node $node): bool
    {
        $key = $node->aggregateId->value . '@' . $node->workspaceName->value;
        if (!isset(self::$editPermissions[$key])) {
            $objectManager = Bootstrap::$staticObjectManager;
            $roles = $objectManager->get(SecurityContext::class)->getRoles();
            self::$editPermissions[$key] = $objectManager->get(ContentRepositoryAuthorizationService::class)->getNodePermissions($node, $roles)->edit;
        }
        return self::$editPermissions[$key];
    }

    /** Plain text of an (inline edited) html property, e.g. for select options or meta tags */
    public static function plain(Node $node, string $propertyName): string
    {
        $value = $node->getProperty($propertyName);
        return is_string($value) ? trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5)) : '';
    }
}
