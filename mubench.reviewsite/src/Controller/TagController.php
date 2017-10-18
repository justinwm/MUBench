<?php

namespace MuBench\ReviewSite\Controller;


use MuBench\ReviewSite\Models\Tag;
use Slim\Http\Request;
use Slim\Http\Response;

class TagController extends Controller
{


    public function add(Request $request, Response $response)
    {
        $formData = $request->getParsedBody();
        $tag_name = $formData['tag_name'];
        $misuse_id = $formData['misuse_id'];

        $tag = Tag::firstOrCreate(['name' => $tag_name]);
        $this->database2->table('misuse_tags')->insert(array('misuse_id' => $misuse_id, 'tag_id' => $tag->id));
        // TODO: refactor
        return $response->withRedirect("{$this->site_base_url}index.php/{$formData['path']}");
    }

    public function remove(Request $request, Response $response)
    {
        $formData = $request->getParsedBody();
        $tag_id = $formData['tag_id'];
        $misuse_id = $formData['misuse_id'];
        $this->database2->table('misuse_tags')->where('misuse_id', $misuse_id)->where('tag_id', $tag_id)->delete();
        // TODO: refactor
        return $response->withRedirect("{$this->site_base_url}index.php/{$formData['path']}");
    }
}