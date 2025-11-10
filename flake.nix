{
  inputs.nixpkgs.url = "github:NixOS/nixpkgs/nixos-24.05";

  outputs = { self, nixpkgs }: let
    system = "x86_64-linux";
    pkgs = nixpkgs.legacyPackages.${system};
  in {
    nixosConfigurations.default = nixpkgs.lib.nixosSystem {
      inherit system;
      modules = [ ./.idx/dev.nix ];
    };

    devShells.${system}.default = pkgs.mkShell {
      packages = with pkgs; [
        mysql80
        php83
        php83Packages.composer
        nodejs_20
      ];

      shellHook = ''
        echo "Starting MySQL..."
        sudo systemctl start mysql

        export DB_HOST="localhost"
        export DB_USER="bec_dev"
        export DB_PASS="dev_password"
        export DB_NAME="bec_gallery_dev"
      '';
    };
  };
}
