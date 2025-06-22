# アプリケーション名(ログイン機能あり)

# こっちはブランチ A

-   このリポジトリは amd 版です(Windows/IntelCPU の Mac 向け)

## 準備手順(この手順はアプリ完成時には README から削除する)

-   Docker Desktop で全コンテナを停止させる

-   リモートリポジトリの作成  
    GitHub でリモートリポジトリを作成  
    自由にリポジトリ名を入力したら他の設定は変更せずに作成ボタンを押す  
    作成したら下記を控える  
    新しいリポジトリの SSH => 上で作成した SSH アドレス  
    新しいリポジトリの名前 => 上で自由に入力したリポジトリ名

-   コマンドライン(Ubuntu or Terminal)を開く

-   カレントディレクトリ(現在のディレクトリ)を coachtech に移動する

-   以下のコマンドを実行する

```
git clone git@github.com:tamachima327/laravel-template.git
```

```
yes | rm -r laravel-template/.git
```

```
git clone 新しいリポジトリのSSH
```

```
mv laravel-template/* laravel-template/.[^\.]* 新しいリポジトリの名前
```

```
rm -r laravel-template
```

```
cd 新しいリポジトリの名前
```

```
code .
```

## 環境構築手順

-   コンテナを立ち上げるため、以下を実行

```
docker compose up -d --build
```

-   env ファイルの作成をするため、以下を実行

```
cp src/.env.example src/.env
```

-   php にコンテナに入るため、以下を実行

```
docker compose exec php bash
```

-   composer パッケージをインストールするため、以下を実行

```
composer install
```

-   アプリケーションキーを作成するため、以下を実行

```
php artisan key:generate
```

-   マイグレーションを実行するため、以下を実行

```
php artisan migrate
```

## 環境構築手順が終わった後にやること(この手順はアプリ完成時には README から削除する)

-   ブラウザで動作チェック  
    localhost にアクセスして動作確認  
    localhost:8080 にアクセスして phpmyadmin が見れるか確認

-   環境構築手順で動くことを確認したら commit/push して環境構築完了  
    コミットメッセージは"First commit"

## 開発でやる必要があること(この手順はアプリ完成時には README から削除する)

-   view ファイルの作成・修正・削除
-   controller の作成・修正
-   model の作成・修正
-   css の作成・修正・削除(クラス名も直すこと)
-   migration ファイルの作成・修正
-   seeder の作成
-   README.md(このファイル)の修正
