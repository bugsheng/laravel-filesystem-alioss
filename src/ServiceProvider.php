<?php


namespace BugSheng\Laravel\Filesystem\AliOss;

use BugSheng\Laravel\Filesystem\AliOss\Adapters\AliOssAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use League\Flysystem\Filesystem;
use OSS\OssClient;

class ServiceProvider extends LaravelServiceProvider
{

    public function register()
    {

    }

    public function boot()
    {
        Storage::extend('aliOss', function ($app, $config) {
            //从OSS获得的AccessKeyId
            $accessKeyId = $config['access_key_id'];

            //从OSS获得的AccessKeySecret
            $accessKeySecret = $config['access_key_secret'];

            //选定的OSS数据中心访问域名，例如oss-cn-hangzhou.aliyuncs.com
            $endpoint = $config['endpoint'];

            //是否对Bucket做了域名绑定，并且Endpoint参数填写的是自己的域名
            $isCName = $config['isCName'];
            $cdnDomain = empty($config['cdn_domain']) ? '' : $config['cdn_domain'];
            $epInternal = $isCName ? $cdnDomain : (empty($config['endpoint_internal']) ? $endpoint : $config['endpoint_internal']); // 内部节点

            $securityToken = null;
            $requestProxy = null;

            $bucket = $config['bucket'];
            $ssl = empty($config['ssl']) ? false : $config['ssl'];

            $client = new OssClient($accessKeyId, $accessKeySecret, $epInternal, $isCName, $securityToken,
                $requestProxy);

            $adapter = new AliOssAdapter($client, $bucket, $endpoint, $ssl, $isCName, $cdnDomain);

            return new Filesystem($adapter);
        });
    }

}
