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

    # NOTE: this is not the correct way to apply an overlay!
    packages = self.overlays.default pkgs pkgs;
  in
  {
    packages = {
      inherit (packages) modmail;
      default = packages.modmail;
    };
  }
)
