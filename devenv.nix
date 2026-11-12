
{
  pkgs,
  lib,
  config,
  inputs,
  ...
}:

{
  packages = with pkgs; [
    git
    wp-cli
  ];

  languages.php = {
    enable = true;
    # Pin a specific PHP version if needed for plugin compatibility testing:
    # version = "8.2";
    extensions = [
      "imagick"
      "mysqli"
      "pdo_mysql"
      "xdebug"
    ];
    ini = ''
      memory_limit = 512M
      upload_max_filesize = 64M
      post_max_size = 64M
      xdebug.mode = debug
    '';
    packages = {
      composer = pkgs.phpPackages.composer;
    };
  };

  languages.javascript = {
    enable = true;
    pnpm = {
      enable = true;
      install.enable = true;
    };
  };

  enterShell = ''
    php -v
    wp --info --quiet
    pnpm -v
  '';
}
