<?php
declare(strict_types=1);

namespace customiesdevs\customies\block;

use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

final class Model {

	/** @var Material[] */
	private array $materials;
	private ?string $geometry;
	private BlockSize $blockSize;

	/**
	 * @param Material[] $materials
	 */
	public function __construct(array $materials, ?string $geometry = null, BlockSize|null|Vector3 $origin = null, ?Vector3 $size = null) {
		$this->materials = $materials;
		$this->geometry = $geometry;
		$this->blockSize = $origin instanceof BlockSize ? $origin : BlockSize::BC($origin, $size);
	}

	/**
	 * Returns the model in the correct NBT format supported by the client.
	 * @return CompoundTag[]
	 */
	public function toNBT(): array {
		$materials = CompoundTag::create();
		foreach($this->materials as $material){
			$materials->setTag($material->getTarget(), $material->toNBT());
		}

		$material = [
			"minecraft:material_instances" => CompoundTag::create()
				->setTag("mappings", CompoundTag::create()) // What is this? The client will crash if it is not sent.
				->setTag("materials", $materials),
		];
		if($this->geometry === null) {
			$material["minecraft:unit_cube"] = CompoundTag::create();
		} else {
			$material["minecraft:geometry"] = CompoundTag::create()
				->setString("identifier", $this->geometry);
			[$material["minecraft:collision_box"], $material["minecraft:selection_box"]] = $this->blockSize->toNBT();
		}
		return $material;
	}
}
