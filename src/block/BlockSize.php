<?php
declare(strict_types=1);

namespace customiesdevs\customies\block;

use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;

final class BlockSize {

	public function __construct(
		public bool $collisionBoxEnabled,
		public ?Vector3 $collisionBoxOrigin,
		public ?Vector3 $collisionBoxSize,
		public bool $selectionBoxEnabled,
		public ?Vector3 $selectionBoxOrigin,
		public ?Vector3 $selectionBoxSize
	) {}

	public static function BC(?Vector3 $origin, ?Vector3 $size): BlockSize {
		$origin ??= Vector3::zero();
		$size ??= Vector3::zero();
		return new BlockSize(true, $origin, $size, true, $origin, $size);
	}

	public static function FULL(): BlockSize {
		return new BlockSize(true, null, null, true, null, null);
	}

	public static function NONE(bool $selectionBoxEnabled = false, Vector3 $selectionBoxOrigin = null, Vector3 $selectionBoxSize = null): BlockSize {
		return new BlockSize(false, null, null, $selectionBoxEnabled, $selectionBoxOrigin, $selectionBoxSize);
	}

	public function toNBT(): array {
		return [
			CompoundTag::create()
				->setByte("enabled", $this->collisionBoxEnabled ? 1 : 0)
				->setTag("origin", new ListTag([
					new FloatTag($this->collisionBoxOrigin?->getX() ?? -8),
					new FloatTag($this->collisionBoxOrigin?->getY() ?? 0),
					new FloatTag($this->collisionBoxOrigin?->getZ() ?? -8)
				]))
				->setTag("size", new ListTag([
					new FloatTag($this->collisionBoxSize?->getX() ?? 16),
					new FloatTag($this->collisionBoxSize?->getY() ?? 16),
					new FloatTag($this->collisionBoxSize?->getZ() ?? 16)
				])),
			CompoundTag::create()
				->setByte("enabled", $this->selectionBoxEnabled ? 1 : 0)
				->setTag("origin", new ListTag([
					new FloatTag($this->selectionBoxOrigin?->getX() ?? -8),
					new FloatTag($this->selectionBoxOrigin?->getY() ?? 0),
					new FloatTag($this->selectionBoxOrigin?->getZ() ?? -8)
				]))
				->setTag("size", new ListTag([
					new FloatTag($this->selectionBoxSize?->getX() ?? 16),
					new FloatTag($this->selectionBoxSize?->getY() ?? 16),
					new FloatTag($this->selectionBoxSize?->getZ() ?? 16)
				]))
		];
	}
}
