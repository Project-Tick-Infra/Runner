{
  config,
  lib,
  pkgs,
  ...
}:
let
  inherit (lib)
    getExe
    literalExpression
    mapAttrs
    mkEnableOption
    mkIf
    mkOption
    mkPackageOption
    optionals
    types
    ;

  cfg = config.services.modmail;
in
{
  options.services.modmail = {
    enable = mkEnableOption "Modmail";

    package = mkPackageOption pkgs "modmail" { };

    settings = mkOption {
      default = { };
      description = ''
        Environment variables for the main application.
      '';
      example = {
        LOG_URL = "https://foo.example.com/";
        GUILD_ID = 1234567890;
        OWNERS = "98765,43210";
      };
      type =
        with types;
        attrsOf (oneOf [
          str
          int
        ]);
    };

    environmentFile = mkOption {
      description = ''
        Path to environment file containing secrets such as TOKEN or CONNECTION_URI.
      '';
      example = literalExpression "config.age.secrets.modmail.path";
      # https://github.com/NixOS/nixpkgs/pull/78640
      type = types.str // {
        check = it: types.str.check it && types.path.check it;
      };
    };
  };

  config = mkIf cfg.enable {
    systemd.services."modmail" = {
      description = "Modmail Discord bot";
      wantedBy = [ "multi-user.target" ];
      after = [ "network.target" ] ++ optionals config.services.ferretdb.enable [ "ferretdb.service" ];

      environment = mapAttrs (_: toString) cfg.settings;

      serviceConfig = {
        ExecStart = getExe cfg.package;
        EnvironmentFile = [ cfg.environmentFile ];
        LogsDirectory = "modmail";
        WorkingDirectory = "%L/modmail";

        DynamicUser = true;

        # TODO: hardening
      };
    };
  };
}
