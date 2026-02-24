{
  description = "Description for the project";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    flake-utils.url = "github:numtide/flake-utils";
    treefmt-nix = {
      url = "github:numtide/treefmt-nix";
      inputs.nixpkgs.follows = "nixpkgs";
    };
  };

  outputs =
    { flake-utils, ... }@inputs:
    flake-utils.lib.meld inputs [
      ./development.nix
      ./nixos
      ./pkgs
      ./pkgs/overlay.nix
    ];
}
