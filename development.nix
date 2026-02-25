{
  flake-utils,
  nixpkgs,
  self,
  treefmt-nix,
  ...
}:
flake-utils.lib.eachDefaultSystem (
  system:
  let
    pkgs = nixpkgs.legacyPackages.${system};
    inherit (pkgs.lib) mapAttrs' nameValuePair;

    treefmtEval = treefmt-nix.lib.evalModule pkgs ./treefmt.nix;

    devShellChecks = mapAttrs' (n: nameValuePair "devShell-${n}") self.devShells.${system};
  in
  {
    devShells.default = pkgs.mkShell {
      inputsFrom = [ self.packages.${system}.default ];
      packages = [
        pkgs.ruff
      ];
    };

    checks = devShellChecks // {
      formatting = treefmtEval.config.build.check self;
    };

    formatter = treefmtEval.config.build.wrapper;
  }
)
