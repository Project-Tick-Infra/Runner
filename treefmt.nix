# SPDX-FileCopyrightText: 2024 Sefa Eyeoglu <contact@scrumplex.net>
#
# SPDX-License-Identifier: MIT
{ ... }:
{
  projectRootFile = "flake.nix";

  programs.actionlint.enable = true;
  programs.mdformat.enable = true;
  programs.nixfmt.enable = true;
  programs.ruff.enable = true;
}
