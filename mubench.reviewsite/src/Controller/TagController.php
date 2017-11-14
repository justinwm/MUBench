<?php

namespace MuBench\ReviewSite\Controller;


use Illuminate\Database\QueryException;
use MuBench\ReviewSite\Models\Misuse;
use MuBench\ReviewSite\Models\Tag;
use Slim\Http\Request;
use Slim\Http\Response;

class TagController extends Controller
{
    public function postTag(Request $request, Response $response, array $args)
    {
        $formData = $request->getParsedBody();
        $tag_id = $formData['tag_name'];
        $misuse_id = $args['misuse_id'];

        $this->addTagToMisuse($misuse_id, $tag_id);

        return $response->withRedirect("{$this->site_base_url}index.php/{$formData['path']}");
    }

    public function deleteTag(Request $request, Response $response, array $args)
    {
        $formData = $request->getParsedBody();
        $tag_id = $args['tag_id'];
        $misuse_id = $args['misuse_id'];

        $this->deleteTagFromMisuse($misuse_id, $tag_id);

        return $response->withRedirect("{$this->site_base_url}index.php/{$formData['path']}");
    }

    function addTagToMisuse($misuseId, $tagName)
    {
        $tag = Tag::firstOrCreate(['name' => $tagName]);
        Misuse::find($misuseId)->misuse_tags()->syncWithoutDetaching($tag->id);
    }

    function deleteTagFromMisuse($misuseId, $tagId)
    {
        Misuse::find($misuseId)->misuse_tags()->detach($tagId);
    }
}
