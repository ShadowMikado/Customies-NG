<?php
declare(strict_types=1);

namespace customiesdevs\customies\block\permutations;

use pocketmine\block\Block;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

trait RotatableTrait {
	use HorizontalFacingTrait;

	/**
	 * @return BlockProperty[]
	 */
	public function getBlockProperties(): array {
		return [
			new BlockProperty("customies:rotation", [2, 3, 4, 5]),
		];
	}

	/**
	 * @return Permutation[]
	 */
	public function getPermutations(): array {
		return [
			(new Permutation("q.block_property('customies:rotation') == 2"))
				->withComponent("minecraft:rotation", CompoundTag::create()
					->setFloat("x", 0)
					->setFloat("y", 0)
					->setFloat("z", 0)),
			(new Permutation("q.block_property('customies:rotation') == 3"))
				->withComponent("minecraft:rotation", CompoundTag::create()
					->setFloat("x", 0)
					->setFloat("y", 180)
					->setFloat("z", 0)),
			(new Permutation("q.block_property('customies:rotation') == 4"))
				->withComponent("minecraft:rotation", CompoundTag::create()
					->setFloat("x", 0)
					->setFloat("y", 90)
					->setFloat("z", 0)),
			(new Permutation("q.block_property('customies:rotation') == 5"))
				->withComponent("minecraft:rotation", CompoundTag::create()
					->setFloat("x", 0)
					->setFloat("y", 270)
					->setFloat("z", 0)),
		];
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null): bool {
		if($player !== null) {
			$this->facing = $player->getHorizontalFacing();
		}
		return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}
}