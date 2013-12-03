if RUBY_VERSION =~ /1.9/
	Encoding.default_external = Encoding::UTF_8
	Encoding.default_internal = Encoding::UTF_8
end
relative_assets = true
css_dir = "../../Public/Styles"
sass_dir = "."
images_dir = "Images"
images_path = "../Images"
output_style = :expanded
environment = :production