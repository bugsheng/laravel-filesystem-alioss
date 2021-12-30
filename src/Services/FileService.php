<?php
/**
 * Created by PhpStorm.
 * User: shenglin
 * Date: 2019/4/20
 * Time: 12:40
 */

namespace BugSheng\Laravel\Filesystem\AliOss\Services;

use BugSheng\Functional\Respond\FunctionalRespondTrait;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\Flysystem\AdapterInterface;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Storage;

/**
 * 文件存储至阿里云存储服务
 *
 * Class AliOssService
 *
 * @package App\Modules\Api\Oss\Services
 */
class FileService
{

    use FunctionalRespondTrait;

    /**
     * @var string
     */
    protected $disk = 'aliOss';

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $storage;

    /**
     * AliOssService constructor.
     *
     */
    public function __construct()
    {
        $this->storage = Storage::disk($this->disk);
    }

    //**************** 读取数据 *******************//

    /**
     * 根据路径获取url
     *
     * @param string $file_path 文件地址
     * @param bool   $is_public 文件权限
     *
     * @return string
     */
    public function getOssFileUrl($file_path, $is_public = true)
    {
        try {
            $file_path = (Str::startsWith($file_path, '/') ? ltrim($file_path, '/') : $file_path);

            return $is_public ? $this->storage->url($file_path) : $this->storage->temporaryUrl($file_path,
                Carbon::now()->addMinutes(60));
        } catch (\Exception $e) {
            Log::error('获取文件地址异常');
            Log::error('文件地址:' . $file_path);
            Log::error('文件权限:' . ($is_public ? '公开' : '私有'));
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return '';
        }
    }

    /**
     * 根据文件路径读取流
     *
     * @param string $file_path 文件完整路径
     *
     * @return false|null|resource
     */
    public function readOssFile($file_path)
    {
        try {
            $file_path = (Str::startsWith($file_path, '/') ? ltrim($file_path, '/') : $file_path);

            return $this->storage->read($file_path);
        } catch (\Exception $e) {
            return false;
        }
    }

    //**************** 存储数据 *******************//

    /**
     * 文件流上传
     *
     * @param string $file_dir
     * @param string $file_name
     * @param string $file_ext
     * @param string $mime
     * @param string $content
     * @param bool   $is_public
     *
     * @return array|mixed
     */
    public function storeOssStream($file_dir, $file_name, $file_ext, $mime, $content, $is_public = true)
    {

        $new_filename = Uuid::uuid1()->getHex() . '.' . $file_ext; // 唯一文件名

        $file_dir = $this->trimDir($file_dir);

        $file_path = $file_dir . '/' . $new_filename;

        $options = [
            'disk'       => $this->disk,
            'visibility' => $is_public ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE
        ];

        $result = $this->storage->put($file_path, $content, $options);

        if (!$result) {
            return $this->fail('文件流上传失败');
        }

        $url = $this->getOssFileUrl($file_path, $is_public);
        if ($url == '') {
            return $this->fail('文件流上传失败');
        }

        $data = [
            'origin_name' => $file_name,
            'save_name'   => $new_filename,
            'save_dir'    => $file_dir,
            'save_path'   => $file_path,
            'ext'         => $file_ext,
            'mime'        => $mime,
            'location'    => $this->disk,
            'is_public'   => $is_public
        ];

        return $this->success($data);
    }

    /**
     * 存储web服务器上传文件
     *
     * @param string       $file_dir
     * @param UploadedFile $file
     * @param bool         $is_public
     *
     * @return array|false
     */
    public function storeUploadFile($file_dir, UploadedFile $file, $is_public = true)
    {
        $file_dir = $this->trimDir($file_dir);
        $options  = [
            'disk'       => $this->disk,
            'visibility' => $is_public ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE
        ];

        $save_path = $file->store($file_dir, $options);
        if ($save_path === false) {
            return false;
        }

        return [
            'original_name' => $file->getClientOriginalName(),
            'save_name'     => ltrim($save_path, $file_dir . '/'),
            'save_dir'      => $file_dir,
            'save_path'     => $save_path,
            'ext'           => $file->getClientOriginalExtension(),
            'mime'          => $file->getClientMimeType(),
            'location'      => $options['disk']
        ];

    }

    /**
     * 存储web服务器上传文件
     *
     * @param string $file_dir
     * @param File   $file
     * @param bool   $is_public 文件是否公开可见
     *
     * @return array|mixed
     */
    public function storeFile($file_dir, File $file, $is_public = true)
    {
        $file_dir = $this->trimDir($file_dir);
        $options  = [
            'disk'       => $this->disk,
            'visibility' => $is_public ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE
        ];

        $fp  = fopen($file->getPathname(), "rb");
        $buf = fread($fp, $file->getSize());
        fclose($fp);
        $new_filename = Uuid::uuid1()->getHex() . '.' . $file->getExtension();    //32位字符串方法

        $save_path = $file_dir . '/' . $new_filename;

        $result = $this->storage->put($save_path, $buf, $options);
        if ($result === false) {
            return $this->fail('文件上传处理失败');
        }

        $url = $this->getOssFileUrl($save_path, $is_public);
        if ($url == '') {
            return $this->fail('上传文件失败');
        }

        $data = [
            'origin_name' => $file->getFilename(),
            'save_name'   => $new_filename,
            'save_dir'    => $file_dir,
            'save_path'   => $save_path,
            'ext'         => $file->getExtension(),
            'mime'        => $file->getMimeType(),
            'size'        => $file->getSize(),
            'location'    => $this->disk,
            'is_public'   => $is_public
        ];

        return $this->success($data);
    }

    /**
     * 批量存储web服务器上传文件
     *
     * @param string $file_dir
     * @param array  $files
     * @param bool   $is_public 文件是否公开可见
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function storeFiles($file_dir, array $files, $is_public = true)
    {
        $data = [];
        foreach ($files as $file) {
            $result = self::storeFile($file_dir, $file, $is_public);
            if ($result['status'] == false) {

                //如果之前有文件上传成功的，删除文件
                if (count($data)) {
                    self::deleteSameDirOssFiles($file_dir, Arr::pluck($data, 'save_name'));
                }

                return $this->fail('文件上传失败');
            }
            $data[] = $result['data'];
        }

        return $this->success($data);
    }


    //**************** 删除数据 *******************//

    /**
     * 删除单个文件
     *
     * @param string $file_dir
     * @param string $file_name
     *
     * @return array
     */
    public function deleteOssFile($file_dir, $file_name)
    {

        $file_dir = $this->trimDir($file_dir);

        $file = $file_dir . '/' . $file_name;

        $result = $this->storage->delete($file);

        if ($result == false) {
            return $this->fail();
        }

        return $this->success();
    }

    /**
     * 批量删除阿里云Oss文件（同一个目录下）
     *
     * @param string $file_dir
     * @param array  $file_names
     *
     * @return array
     */
    public function deleteSameDirOssFiles($file_dir, array $file_names)
    {

        $file_dir = $this->trimDir($file_dir);

        $files = [];
        foreach ($file_names as $k => $item) {
            $files[] = $file_dir . '/' . $item;
        }
        if (!$files) {
            return $this->fail('请选择文件');
        }

        $result = $this->storage->delete($file_names);

        if ($result['status'] == false) {
            return $this->fail();
        }

        return $this->success();
    }

    /**
     * 删除目录
     *
     * @param string $file_dir
     *
     * @return array|mixed
     */
    public function deleteOssDir($file_dir)
    {

        $file_dir = $this->trimDir($file_dir);

        $result = $this->storage->deleteDir($file_dir);

        if ($result === false) {
            return $this->fail();
        }

        return $this->success();
    }

    //**************** 通用方法 *******************//

    /**
     * 处理目录字段的前后的'/'字符
     *
     * @param string $dir
     *
     * @return string
     */
    protected function trimDir($dir)
    {
        $dir = (Str::startsWith($dir, '/') ? ltrim($dir, '/') : $dir);
        $dir = (Str::endsWith($dir, '/') ? rtrim($dir, '/') : $dir);

        return $dir;
    }

}
