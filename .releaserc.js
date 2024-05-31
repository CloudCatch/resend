module.exports = {
	tagFormat: "${version}",
	branches: ["master"],
	plugins: [
		["@semantic-release/npm", { npmPublish: false }],
		"@semantic-release/commit-analyzer",
		{
			preset: "angular",
			releaseRules: [
				{ type: "docs", release: "patch" },
			],
		},
		"@semantic-release/github",
		[
			"semantic-release-plugin-update-version-in-files",
			{
				files: [
					"resend.php",
					"readme.txt",
				],
				placeholder: "0.0.0-development",
			},
		],
	],
};