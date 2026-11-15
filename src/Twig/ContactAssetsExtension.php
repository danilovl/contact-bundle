<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Twig;

use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContactAssetsExtension extends AbstractExtension
{
    private const string ENTRYPOINT_NAME = 'contact';

    private const string BUNDLE_PUBLIC_PATH = 'bundles/contact/build/';

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly Packages $packages
    ) {}

    /**
     * @return array<int, TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('danilovl_contact_assets', $this->renderAssets(...), ['is_safe' => ['html']])
        ];
    }

    public function renderAssets(): string
    {
        $entrypointsPath = $this->projectDir . '/public/' . self::BUNDLE_PUBLIC_PATH . 'entrypoints.json';
        if (!is_file($entrypointsPath)) {
            return sprintf('<!-- ContactBundle: entrypoints.json not found at %s, run assets:install -->', $entrypointsPath);
        }

        $assets = $this->loadAssets($entrypointsPath);
        if ($assets === null) {
            return sprintf('<!-- ContactBundle: entrypoint "%s" not found -->', self::ENTRYPOINT_NAME);
        }

        $html = '';

        foreach ($assets['css'] as $css) {
            $url = $this->packages->getUrl($css);
            $html .= sprintf('<link rel="stylesheet" href="%s">', $url);
        }

        foreach ($assets['js'] as $js) {
            $url = $this->packages->getUrl($js);
            $html .= sprintf('<script src="%s" defer></script>', $url);
        }

        return $html;
    }

    /**
     * @return array{css: list<string>, js: list<string>}|null
     */
    private function loadAssets(string $entrypointsPath): ?array
    {
        $content = file_get_contents($entrypointsPath);
        if ($content === false) {
            return null;
        }

        $entrypoints = json_decode($content, true);
        if (!is_array($entrypoints) || !is_array($entrypoints['entrypoints'] ?? null)) {
            return null;
        }

        $assets = $entrypoints['entrypoints'][self::ENTRYPOINT_NAME] ?? null;
        if (!is_array($assets)) {
            return null;
        }

        $css = $this->filterStrings($assets['css'] ?? []);
        $js = $this->filterStrings($assets['js'] ?? []);

        return [
            'css' => $css,
            'js' => $js
        ];
    }

    /**
     * @return list<string>
     */
    private function filterStrings(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $result = [];
        foreach ($values as $value) {
            if (is_string($value)) {
                $result[] = $value;
            }
        }

        return $result;
    }
}
