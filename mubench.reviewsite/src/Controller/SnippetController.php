<?php
/**
 * Created by PhpStorm.
 * User: jonas
 * Date: 18.10.2017
 * Time: 21:30
 */

namespace MuBench\ReviewSite\Controller;


use MuBench\ReviewSite\Models\Snippet;
use Slim\Http\Request;
use Slim\Http\Response;

class SnippetController extends Controller
{

    public function add(Request $request, Response $response, array $args)
    {
        $form = $request->getParsedBody();
        Snippet::create(['snippet'=> $form['snippet'], 'line' => $form['line'], 'misuse_id' => $form['misuse_id']]);
        return $response->withRedirect("{$this->site_base_url}index.php/{$form['path']}");
    }

    public function remove(Request $request, Response $response, array $args)
    {
        $form = $request->getParsedBody();
        $snippet = Snippet::find($args['snippet_id']);
        $snippet->delete();
        return $response->withRedirect("{$this->site_base_url}index.php/{$form['path']}");
    }

}
