## Introduction
NCGR2PNG is a class that is able to load NCGR and NCLR files and directly convert and output them to png files. NCGR (Nintendo DS Title Graphics) files are files used by DS games to store the shape of sprites while NCLR (Nintendo DS Color Palette) files contain a color palette that can be applied to a NCGR file. Since they can't be displayed by a normal program they have to be converted into a format that can easily be displayed on a variety of systems.

## Usage
To use the class simply include it in your PHP file.

	require_once("ncgr2png.php");

When constructing an instance of this class, it is possible to directly set an NCGR and NCLR file. It is also possible to do this at a later point or overwrite an existing NCGR or NCLR file. Setting such a file requires you to give a path to said file so that it can be read by the program. Converting two files to a png file will directly save it to the given directory. If either the NCGR or NCLR file has not been set before calling this function it will throw an error. Note that all sprites have a default transparent color. This color will not be added to the sprite, instead it will be left transparent. The sprite will also not be cropped, if you want it to be cropped you have to do this yourself afterwards.