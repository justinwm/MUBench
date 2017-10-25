<?php

namespace MuBench\ReviewSite\Controller;


use Illuminate\Database\QueryException;
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

        $this->tagMisuse($tag_name, $misuse_id);
        // TODO: refactor
        return $response->withRedirect("{$this->site_base_url}index.php/{$formData['path']}");
    }

    public function remove(Request $request, Response $response)
    {
        $formData = $request->getParsedBody();
        $tag_id = $formData['tag_id'];
        $misuse_id = $formData['misuse_id'];
        $this->removeTag($tag_id, $misuse_id);
        // TODO: refactor
        return $response->withRedirect("{$this->site_base_url}index.php/{$formData['path']}");
    }

    public function tagMisuse($tagName, $misuseId)
    {
        $tag = Tag::firstOrCreate(['name' => $tagName]);
        try{
            $this->database2->table('misuse_tags')->insert(array('misuse_id' => $misuseId, 'tag_id' => $tag->id));
        }catch(QueryException $exception){

        }
    }

    public function removeTag($tagId, $misuseId)
    {
        $this->database2->table('misuse_tags')->where('misuse_id', $misuseId)->where('tag_id', $tagId)->delete();
    }
}