{
  flake-utils,
  nixpkgs,
  self,
  ...
}:
flake-utils.lib.eachDefaultSystem (
  system:
  let
    pkgs = nixpkgs.legacyPackages.${system};
  in
  {
    checks.modmail-module = pkgs.nixosTest {
      name = "modmail-module-test";

      nodes.machine =
        { pkgs, ... }:
        {
          imports = [ self.nixosModules.modmail-with-overlay ];

          # TODO: emulate Discord API?

          services.ferretdb.enable = true;

          services.modmail = {
            enable = true;
            settings = {
              LOG_URL = "https://foo.example.com/";
              GUILD_ID = 1234567890;
              OWNERS = "98765,43210";
            };

            environmentFile = toString (
              pkgs.writeText "modmail.env" ''
                CONNECTION_URI="mongodb://127.0.0.1/ferretdb"
                TOKEN=foo
              ''
            );
          };
        };

      testScript = ''
        start_all()
        machine.wait_for_unit("modmail.service")
        machine.succeed("systemctl status modmail.service")
      '';
    };
  }
)
