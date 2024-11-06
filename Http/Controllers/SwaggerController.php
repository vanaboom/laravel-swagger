<?php

namespace VanaBoom\LaravelSwagger\Http\Controllers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Support\Str;
use VanaBoom\LaravelSwagger\Exceptions\ExtensionNotLoaded;
use VanaBoom\LaravelSwagger\Exceptions\InvalidAuthenticationFlow;
use VanaBoom\LaravelSwagger\Exceptions\InvalidFormatException;
use VanaBoom\LaravelSwagger\Formatter;
use VanaBoom\LaravelSwagger\Generator;

/**
 * Class SwaggerController
 */
class SwaggerController extends BaseController
{
    /**
     * Configuration repository
     */
    protected Repository $configuration;

    /**
     * SwaggerController constructor.
     */
    public function __construct(Repository $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Return documentation content
     *
     * @throws ExtensionNotLoaded|InvalidFormatException|InvalidAuthenticationFlow
     */
    public function documentation(Request $request): Response
    {
        $documentation = swagger_resolve_documentation_file_path();
        if (strlen($documentation) === 0) {
            abort(404, sprintf('Please generate documentation first, then access this page'));
        }
        if (config('swagger.generated', false)) {
            dd($documentation);

            $documentation = (new Generator($this->configuration))->generate();

            return ResponseFacade::make((new Formatter($documentation))->setFormat('json')->format(), 200, [
                'Content-Type' => 'application/json',
            ]);
        }

        $content = File::get($documentation);
        $yaml = Str::endsWith('yaml', pathinfo($documentation, PATHINFO_EXTENSION));
        if ($yaml) {
            return ResponseFacade::make($content, 200, [
                'Content-Type' => 'application/yaml',
                'Content-Disposition' => 'inline',
            ]);
        }

        return ResponseFacade::make($content, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Render Swagger UI page
     */
    public function api(Request $request): Response
    {
        $url = config('app.url');
        if (! Str::startsWith($url, 'http://') && ! Str::startsWith($url, 'https://')) {
            $schema = swagger_is_connection_secure() ? 'https://' : 'http://';
            $url = $schema.$url;
        }

        return ResponseFacade::make(view('swagger::index', [
            'secure' => swagger_is_connection_secure(),
            'urlToDocs' => $url.config('swagger.path', '/documentation').'/content',
        ]), 200);
    }
}
