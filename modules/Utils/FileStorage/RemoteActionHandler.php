<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__ . '/ActionHandler.php';

class Utils_FileStorage_RemoteActionHandler
    extends Utils_FileStorage_ActionHandler
{
    protected $forUsersOnly = false; // override to allow external downloads

    protected function hasAccess($action, $request)
    {
        return !$this->forUsersOnly;
    }

    protected function getFile(Request $request, $disposition)
    {
        $id = $request->query->get('id');
        $token = $request->query->get('token');
        $remote = DB::GetRow('SELECT expires_on, uf.id FROM utils_filestorage_remote as ufr INNER JOIN utils_filestorage as uf ON ufr.file_id = uf.id  WHERE ufr.id=%d AND ufr.token=%s',[$id,$token]);
        if(strtotime($remote['expires_on']) < time()) {
            return new Response('File has expired', 404);
        }
        $request->query->set('id',$remote['id']);
        return parent::getFile($request, $disposition);
    }

}
