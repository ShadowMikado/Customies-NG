<?php
declare(strict_types=1);

namespace customiesdevs\customies\item;

use customiesdevs\customies\util\Cache;
use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\StringToItemParser;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\ItemComponentPacketEntry;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use ReflectionClass;
use RuntimeException;
use function array_values;
use function method_exists;

final class CustomiesItemFactory {
	use SingletonTrait;

	/**
	 * @var ItemTypeEntry[]
	 */
	private array $itemTableEntries = [];
	/**
	 * @var ItemComponentPacketEntry[]
	 */
	private array $itemComponentEntries = [];
	/**
	 * @var Item[]
	 */
	private array $customItems = [];
	private int $nextBlockItemNetworkId = -745;

	/**
	 * Get a custom item from its identifier. An exception will be thrown if the item is not registered.
	 */
	public function get(string $identifier, int $amount = 1): Item {
		$item = $this->customItems[$identifier] ?? throw new InvalidArgumentException("Custom item " . $identifier . " is not registered");
		return (clone $item)->setCount($amount);
	}

	/**
	 * Returns the item properties CompoundTag which maps out all custom item properties.
	 * @return ItemComponentPacketEntry[]
	 */
	public function getItemComponentEntries(): array {
		return $this->itemComponentEntries;
	}

	/**
	 * Returns custom item entries for the StartGamePacket itemTable property.
	 * @return ItemTypeEntry[]
	 */
	public function getItemTableEntries(): array {
		return array_values($this->itemTableEntries);
	}

	/**
	 * Registers the item to the item factory and assigns it an ID. It also updates the required mappings and stores the
	 * item components if present.
	 * @phpstan-param class-string $className
	 */
	public function registerItem(string $className, string $identifier, string $name): void {
		if($className !== Item::class) {
			Utils::testValidInstance($className, Item::class);
		}

		/** @var Item $item */
		$item = new $className(new ItemIdentifier(Cache::getInstance()->getNextAvailableItemID($identifier)), $name);

		if(isset($this->customItems[$identifier])) {
			throw new RuntimeException("Item with ID $identifier is already registered");
		}
		$networkId = $item->getTypeId() - 24550;
		$this->registerCustomItemMapping($identifier, $networkId);
		GlobalItemDataHandlers::getSerializer()->map($item, fn() => new SavedItemData($identifier));
		GlobalItemDataHandlers::getDeserializer()->map($identifier, fn() => clone $item);

		if(($componentBased = $item instanceof ItemComponents)) {
			$componentsTag = $item->getComponents();
			$componentsTag->setInt("id", $networkId);
			$componentsTag->setString("name", $identifier);
			$this->itemComponentEntries[$identifier] = new ItemComponentPacketEntry($identifier, new CacheableNbt($componentsTag));
		}

		$this->itemTableEntries[$identifier] = new ItemTypeEntry($identifier, $networkId, $componentBased);
		CreativeInventory::getInstance()->add($item);
		StringToItemParser::getInstance()->register($identifier, fn() => clone $item);
		$this->customItems[$identifier] = $item;
	}

	/**
	 * Registers a custom item ID to the required mappings in the ItemTranslator instance.
	 */
	private function registerCustomItemMapping(string $stringId, int $id): void {
		if(method_exists(GlobalItemTypeDictionary::class, "convertProtocol")){
			foreach(GlobalItemTypeDictionary::getAll(true) as $globalItemTypeDictionary){
				$this->registerCustomItemMappingToDictionary($globalItemTypeDictionary->getDictionary(), $stringId, $id);
			}
		}else{
			$this->registerCustomItemMappingToDictionary(GlobalItemTypeDictionary::getInstance()->getDictionary(), $stringId, $id);
		}
	}

	private function registerCustomItemMappingToDictionary(ItemTypeDictionary $dictionary, string $stringId, int $id): void {
		$reflection = new ReflectionClass($dictionary);

		$reflectionProperty = $reflection->getProperty("intToStringIdMap");
		$reflectionProperty->setAccessible(true);
		/** @var string[] $value */
		$value = $reflectionProperty->getValue($dictionary);
		$reflectionProperty->setValue($dictionary, $value + [$id => $stringId]);

		$reflectionProperty = $reflection->getProperty("stringToIntMap");
		$reflectionProperty->setAccessible(true);
		/** @var int[] $value */
		$value = $reflectionProperty->getValue($dictionary);
		$reflectionProperty->setValue($dictionary, $value + [$stringId => $id]);
	}

	/**
	 * Registers the required mappings for the block to become an item that can be placed etc. It is assigned an ID that
	 * correlates to its block ID.
	 */
	public function registerBlockItem(string $identifier, Block $block): void {
		$itemId = $this->nextBlockItemNetworkId--;
		$this->registerCustomItemMapping($identifier, $itemId);
		$this->itemTableEntries[] = new ItemTypeEntry($identifier, $itemId, false);
		StringToItemParser::getInstance()->registerBlock($identifier, fn() => $block);
	}
}
