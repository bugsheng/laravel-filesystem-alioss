# laravel-filesystem-alioss
适配Laravel框架文件系统的阿里云文件存储(OSS)的扩展包

### 安装

#### 1. 获取扩展内容
```shell script
composer require bugsheng/laravel-filesystem-alioss
```

#### 2. 设置配置项
    
1. 在主项目(laravel框架) config/filesystems.php 中 添加 aliOss 驱动的相关配置

```
    ...
    
    'disks' => [
        ...
        
        'aliOss' => [
            'driver'            => 'aliOss',
            'access_key_id'     => env('ALI_OSS_ACCESS_KEY_ID', ''),
            //从OSS获得的AccessKeyId
            'access_key_secret' => env('ALI_OSS_ACCESS_KEY_SECRET', ''),
            //从OSS获得的AccessKeySecret
            'bucket'            => env('ALI_OSS_BUCKET', ''),
            //OSS中设置的空间bucket
            'cdn_domain'        => env('ALI_OSS_CDN_DOMAIN'),
            // 如果isCName为true, getUrl会判断cdnDomain是否设定来决定返回的url，如果cdnDomain未设置，则使用endpoint来生成url，否则使用cdn
            'endpoint'          => env('ALI_OSS_ENDPOINT', 'oss-cn-hangzhou.aliyuncs.com'),
            //您选定的OSS数据中心访问域名，例如oss-cn-hangzhou.aliyuncs.com
            'endpoint_internal' => env('ALI_OSS_ENDPOINT_INTERNAL', ''),
            //内网地址
            'isCName'           => env('ALI_OSS_IS_CNAME', false),
            //<true|false>是否对Bucket做了域名绑定，并且Endpoint参数填写的是自己的域名
            'ssl'               => env('ALI_OSS_SSL', false),
            //<true|false>是否使用ssl 即链接是否使用https
        ]
        
    ]
    ...
```

2. 根据自身环境调整设置.env文件

```
    ...
    
    #阿里access
    ALI_ACCESS_KEY_ID=
    ALI_ACCESS_KEY_SECRET=
    
    #阿里云存储
    ##阿里云密钥key
    ALI_OSS_ACCESS_KEY_ID="${ALI_ACCESS_KEY_ID}"
    ##阿里云密钥secret
    ALI_OSS_ACCESS_KEY_SECRET="${ALI_ACCESS_KEY_SECRET}"
    ##阿里云网关地址
    ALI_OSS_ENDPOINT=
    ##阿里云内网网关地址 外网使用时 请勿填写该配置内容
    ALI_OSS_ENDPOINT_INTERNAL=
    ##阿里云OSS存储空间
    ALI_OSS_BUCKET=
    ##是否使用CDN
    ALI_OSS_CDN_DOMAIN=
    ##是否使用SSL证书
    ALI_OSS_SSL=
    ##是否使用自定义域名
    ALI_OSS_IS_CNAME=
```

